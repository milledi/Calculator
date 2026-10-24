import { expect, test } from "@playwright/test";
import { calculator, spaced } from "./helpers";

/** Расчёт: продукты, сумма и срок, нагрузка на доход */
test.describe("Калькулятор", () => {
  test.beforeEach(async ({ page }) => {
    await page.goto("/");
  });

  test("стартовый расчёт: ипотека 5 млн на 10 лет", async ({ page }) => {
    const c = calculator(page);

    await expect(c.productRadio("Ипотека")).toBeChecked();
    await expect(c.amount).toHaveValue(spaced("5 000 000"));
    await expect(c.term).toHaveValue("120");
    await expect(c.monthlyPayment).toHaveText("90 093 ₽");
    await expect(c.rateRow).toHaveText("18% годовых");
    await expect(c.termRow).toHaveText("10 лет · 120 платежей");
    await expect(c.totalRow).toHaveText("10 811 112 ₽");
    await expect(c.requiredIncome).toHaveText("от 180 200 ₽");
  });

  test("смена продукта прижимает сумму и срок к его лимитам", async ({ page }) => {
    const c = calculator(page);

    await c.product("Деньги до зарплаты").click();

    await expect(c.productRadio("Деньги до зарплаты")).toBeChecked();
    await expect(c.amount).toHaveValue(spaced("100 000"));
    await expect(c.term).toHaveValue("12");
    await expect(c.summary).toContainText("Деньги до зарплаты");
    await expect(c.rateRow).toHaveText("50% годовых");
    await expect(c.monthlyPayment).toHaveText("10 759 ₽");
    await expect(c.totalRow).toHaveText("129 102 ₽");
  });

  test("сумма и срок с клавиатуры пересчитывают платёж", async ({ page }) => {
    const c = calculator(page);

    await c.product("Автокредит").click();
    await c.amount.fill("1500000");
    await c.term.fill("60");

    await expect(c.amount).toHaveValue(spaced("1 500 000"));
    await expect(c.amountSlider).toHaveValue("1500000");
    await expect(c.monthlyPayment).toHaveText("42 718 ₽");
    await expect(c.termRow).toHaveText("5 лет · 60 платежей");
    await expect(c.totalRow).toHaveText("2 563 063 ₽");
    await expect(c.summary.getByRole("img")).toHaveAccessibleName(
      "Сумма кредита — 59% выплат, проценты — 41%",
    );
  });

  test("сумма выше лимита прижимается к максимуму, ползунок двигается стрелками", async ({ page }) => {
    const c = calculator(page);

    await c.product("Автокредит").click();
    await c.term.fill("60");
    await c.amount.fill("9000000");
    // пока поле в фокусе, расчёт уже идёт по максимуму продукта
    await expect(c.monthlyPayment).toHaveText("142 392 ₽");

    await c.amount.press("Tab");
    await expect(c.amount).toHaveValue(spaced("5 000 000"));
    await expect(c.amountSlider).toBeFocused();

    await c.amountSlider.press("ArrowLeft");
    await expect(c.amount).toHaveValue(spaced("4 950 000"));
  });

  test("шкала нагрузки предупреждает, если платёж больше половины дохода", async ({ page }) => {
    const c = calculator(page);
    await expect(c.summary).toContainText("Укажите доход");

    await c.income.fill("150000");
    await expect(c.loadMeter).toHaveAttribute("aria-valuenow", "61");
    await expect(c.summary).toContainText("Платёж больше половины дохода — нужен доход от 180 200 ₽");

    await c.income.fill("300000");
    await expect(c.loadMeter).toHaveAttribute("aria-valuenow", "31");
    await expect(c.summary).toContainText("Платёж укладывается в половину дохода");
  });
});
