import { expect, test } from "@playwright/test";
import { calculator, fillApplicant, uniqueName } from "./helpers";

/** Заявка: проверки формы и отправка на сервер */
test.describe("Заявка", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/");
  });

  test("пустая форма: ошибки у полей, фокус на ФИО, на сервер ничего не уходит", async ({ page }) => {
    const c = calculator(page);
    let requests = 0;
    page.on("request", (r) => {
      if (r.url().includes("/api/applications")) requests++;
    });

    await c.submit.click();

    await expect(c.fullName).toBeFocused();
    for (const field of [c.fullName, c.income, c.citizenship, c.consent]) {
      await expect(field).toHaveAttribute("aria-invalid", "true");
    }
    await expect(page.getByText("Укажите ФИО (минимум 2 символа)")).toBeVisible();
    await expect(page.getByText("Доход должен быть положительным")).toBeVisible();
    await expect(page.getByText("Требуется гражданство РФ")).toBeVisible();
    await expect(page.getByText("Требуется согласие на обработку персональных данных")).toBeVisible();
    await expect(c.summary).toContainText("Проверьте поля, отмеченные красным");
    expect(requests).toBe(0);
  });

  test("ФИО с цифрами не проходит проверку", async ({ page }) => {
    const c = calculator(page);

    await c.fullName.fill("Иван123");
    await c.fullName.blur();

    await expect(c.fullName).toHaveAttribute("aria-invalid", "true");
    await expect(page.getByText("ФИО может содержать только буквы, пробелы и дефисы")).toBeVisible();
  });

  test("заявка уходит на сервер, сервер считает тот же платёж", async ({ page }) => {
    const c = calculator(page);
    const fullName = uniqueName();

    await fillApplicant(page, { fullName, income: "300000" });
    await expect(c.summary).toContainText("Расчёт предварительный");

    const responsePromise = page.waitForResponse(
      (r) => r.url().endsWith("/api/applications") && r.request().method() === "POST",
    );
    await c.submit.click();
    const response = await responsePromise;

    expect(response.status()).toBe(201);
    expect(response.request().postDataJSON()).toEqual({
      loanType: "mortgage",
      amount: 5_000_000,
      termMonths: 120,
      fullName,
      monthlyIncome: 300_000,
      hasCitizenship: true,
      consentGiven: true,
    });
    const created = await response.json();
    expect(created.monthly_payment).toBe(90_092.6);
    // платёж на экране (в рублях) должен совпадать с серверным
    const shownPayment = Number((await c.monthlyPayment.innerText()).replace(/\D/g, ""));
    expect(shownPayment, "платёж на экране и на сервере").toBe(Math.round(created.monthly_payment));

    await expect(c.pushStatus).toContainText(`Заявка №${created.id} отправлена`);
    await expect(c.pushStatus).toContainText("«Ипотека» на 5 000 000 ₽");
  });

  test("«Новый расчёт» возвращает форму к началу", async ({ page }) => {
    const c = calculator(page);

    await c.product("Автокредит").click();
    await fillApplicant(page, { fullName: uniqueName(), income: "200000" });
    await c.submit.click();
    await expect(c.pushStatus).toContainText("отправлена");

    await page.getByRole("button", { name: "Новый расчёт" }).click();

    await expect(c.productRadio("Ипотека")).toBeChecked();
    await expect(c.monthlyPayment).toHaveText("90 093 ₽");
    await expect(c.fullName).toHaveValue("");
    await expect(c.income).toHaveValue("");
    await expect(c.citizenship).not.toBeChecked();
    await expect(c.consent).not.toBeChecked();
    await expect(c.submit).toBeVisible();
  });
});
