import { expect, type Page } from "@playwright/test";

export const ADMIN = {
  username: process.env.E2E_ADMIN_USER ?? "admin",
  password: process.env.E2E_ADMIN_PASSWORD ?? "admin",
};

/**
 * Числа на сайте разделены неразрывными пробелами («1 500 000»).
 * toHaveText пробелы нормализует сам, а для toHaveValue нужна регулярка.
 */
export function spaced(text: string): RegExp {
  const escaped = text.replace(/[.*+?^${}()|[\]\\]/g, "\\$&").replace(/ /g, "\\s");
  return new RegExp(`^${escaped}$`);
}

/** Уникальное ФИО для теста: только буквы, чтобы пройти валидацию */
export function uniqueName(prefix = "Тестов"): string {
  const letters = "абвгдежзиклмнопрстуфхцчшэюя";
  const suffix = Date.now()
    .toString()
    .split("")
    .map((d) => letters[Number(d) + Math.floor(Math.random() * 17)])
    .join("");
  return `${prefix} ${suffix[0].toUpperCase()}${suffix.slice(1)}`;
}

/** Локаторы калькулятора — по ролям и подписям, как их видит пользователь */
export function calculator(page: Page) {
  const summary = page.getByRole("complementary", { name: "Результат расчёта" });
  const term = (label: string) => summary.locator("dt", { hasText: label }).locator("+ dd");

  return {
    /** Карточка продукта: radio скрыт, кликаем по видимому названию */
    product: (title: string) =>
      page.getByRole("group", { name: "Тип кредита" }).getByText(title, { exact: true }),
    productRadio: (title: string) => page.getByRole("radio", { name: new RegExp(`^${title}`) }),

    amount: page.getByRole("textbox", { name: "Сумма кредита" }),
    amountSlider: page.getByRole("slider", { name: "Сумма кредита, ползунок" }),
    term: page.getByRole("textbox", { name: "Срок кредита" }),
    fullName: page.getByRole("textbox", { name: "ФИО" }),
    income: page.getByRole("textbox", { name: "Ежемесячный доход" }),
    citizenship: page.getByRole("checkbox", { name: "Я гражданин Российской Федерации" }),
    consent: page.getByRole("checkbox", { name: /Согласен на обработку персональных данных/ }),
    submit: page.getByRole("button", { name: "Отправить заявку" }),

    summary,
    monthlyPayment: page.getByTestId("monthly-payment"),
    termRow: term("Срок"),
    rateRow: term("Ставка"),
    totalRow: term("Всего выплат"),
    requiredIncome: page.getByTestId("required-income"),
    loadMeter: page.getByRole("meter", { name: "Доля платежа в доходе" }),
    pushStatus: page.getByTestId("push-status"),
  };
}

/**
 * Галочки тоже скрытые input. Кликаем по началу подписи:
 * в середине подписи согласия стоит ссылка на политику, клик по ней открыл бы PDF.
 */
export async function tick(page: Page, labelStart: string) {
  await page.getByText(labelStart).first().click({ position: { x: 4, y: 6 } });
}

export async function fillApplicant(
  page: Page,
  { fullName, income }: { fullName: string; income: string },
) {
  const c = calculator(page);
  await c.fullName.fill(fullName);
  await c.income.fill(income);
  await tick(page, "Я гражданин Российской Федерации");
  await tick(page, "Согласен на обработку персональных данных");
  await expect(c.citizenship).toBeChecked();
  await expect(c.consent).toBeChecked();
}
