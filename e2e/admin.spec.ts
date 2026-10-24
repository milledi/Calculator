import { expect, test, type APIRequestContext } from "@playwright/test";
import { ADMIN, uniqueName } from "./helpers";

/** Создаёт заявку через API — так тест админки не зависит от формы калькулятора */
async function createApplication(request: APIRequestContext, fullName: string, monthlyIncome = 120_000) {
  const response = await request.post("/api/applications", {
    data: {
      loanType: "auto",
      amount: 1_500_000,
      termMonths: 60,
      fullName,
      monthlyIncome,
      hasCitizenship: true,
      consentGiven: true,
    },
  });
  expect(response.status()).toBe(201);
  return (await response.json()) as { id: number };
}

test.describe("Админка без пароля", () => {
  test("закрыта Basic-авторизацией", async ({ request }) => {
    // Браузер на 401 обрывает навигацию, поэтому проверяем обычным HTTP-запросом
    const response = await request.get("/admin");

    expect(response.status()).toBe(401);
    expect(response.headers()["www-authenticate"]).toContain("Basic");
  });
});

test.describe("Админка", () => {
  test.use({ httpCredentials: ADMIN });

  test("заявка видна в списке, статус меняется и фильтруется", async ({ page, request }) => {
    const fullName = uniqueName("Админов");
    const { id } = await createApplication(request, fullName);

    await page.goto("/admin");
    await expect(page.getByRole("heading", { name: "Заявки на кредиты" })).toBeVisible();

    const row = page.getByRole("row").filter({ hasText: fullName });
    await expect(row).toContainText("Автокредит");
    await expect(row).toContainText("1 500 000 ₽");
    await expect(row).toContainText("42 718 ₽");
    await expect(row).toContainText("120 000 ₽");

    const status = page.getByLabel(`Статус заявки №${id}`);
    await expect(status).toHaveValue("new");

    await status.selectOption({ label: "Одобрена" });
    await row.getByRole("button", { name: "Сохранить" }).click();

    await expect(page).toHaveURL(new RegExp(`updated=${id}`));
    await expect(page.getByRole("status")).toHaveText(`Статус заявки №${id} сохранён`);
    await expect(page.getByLabel(`Статус заявки №${id}`)).toHaveValue("approved");

    await page.getByRole("link", { name: /^Одобренные/ }).click();
    await expect(page.getByRole("row").filter({ hasText: fullName })).toBeVisible();

    await page.getByRole("link", { name: /^Новые/ }).click();
    await expect(page.getByRole("row").filter({ hasText: fullName })).toHaveCount(0);
  });

  test("нагрузка на доход видна в списке, выше 50% — с предупреждением", async ({ page, request }) => {
    const normal = uniqueName("Нормов");
    const risky = uniqueName("Рисков");
    await createApplication(request, normal, 120_000);
    await createApplication(request, risky, 60_000);

    await page.goto("/admin");

    // платёж 42 718 ₽: при доходе 120 000 ₽ это 36%, при 60 000 ₽ — 72%
    const load = (name: string) =>
      page.getByRole("row").filter({ hasText: name }).getByTitle("Доля платежа в доходе");
    await expect(load(normal)).toHaveText("36%");
    await expect(load(risky)).toHaveText("72% — выше 50%");
  });
});
