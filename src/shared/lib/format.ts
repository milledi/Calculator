const NBSP = " ";

/**
 * Форматирование числа с разделителями-пробелами: 1000000 -> "1 000 000".
 * Работает только с целыми без знака; для ввода дробных не предназначено.
 */
export function formatNumber(value: number): string {
  if (!Number.isFinite(value)) return "";
  return Math.round(value)
    .toString()
    .replace(/\B(?=(\d{3})+(?!\d))/g, NBSP);
}

/**
 * Парсинг пользовательского ввода: убирает пробелы (в т.ч. неразрывные)
 * и неразрешённые символы. Возвращает целое число или NaN.
 */
export function parseFormattedNumber(input: string): number {
  const cleaned = input.replace(/[\s ]/g, "").replace(/[^\d]/g, "");
  if (cleaned === "") return NaN;
  return Number.parseInt(cleaned, 10);
}

/** Форматирование валюты для вывода: 25393.42 -> "25 393 ₽" */
export function formatCurrency(value: number): string {
  return `${formatNumber(value)}${NBSP}₽`;
}

/** Короткая запись суммы: 30 000 000 -> "30 млн ₽", 1 500 000 -> "1,5 млн ₽", 100 000 -> "100 тыс. ₽" */
export function formatCompactCurrency(value: number): string {
  if (!Number.isFinite(value)) return "";
  if (value >= 1_000_000) return `${decimal(value / 1_000_000)}${NBSP}млн${NBSP}₽`;
  if (value >= 1_000) return `${decimal(value / 1_000)}${NBSP}тыс.${NBSP}₽`;
  return formatCurrency(value);
}

/** Годовая ставка из долей в проценты: 0.235 -> "23,5%" */
export function formatRate(annualRate: number): string {
  return `${decimal(annualRate * 100)}%`;
}

/** Не более одного знака после запятой, запятая как разделитель: 1.5 -> "1,5", 30 -> "30" */
function decimal(value: number): string {
  return String(Math.round(value * 10) / 10).replace(".", ",");
}
