/** Выбор формы слова по числу: 1 год, 2 года, 5 лет */
export function plural(n: number, one: string, few: string, many: string): string {
  const mod10 = n % 10;
  const mod100 = n % 100;
  if (mod10 === 1 && mod100 !== 11) return one;
  if (mod10 >= 2 && mod10 <= 4 && (mod100 < 12 || mod100 > 14)) return few;
  return many;
}

/** Срок словами: 120 -> "10 лет", 18 -> "1 год 6 месяцев" */
export function formatTerm(months: number): string {
  const years = Math.floor(months / 12);
  const rest = months % 12;
  const parts: string[] = [];
  if (years > 0) parts.push(`${years} ${plural(years, "год", "года", "лет")}`);
  if (rest > 0) parts.push(`${rest} ${plural(rest, "месяц", "месяца", "месяцев")}`);
  return parts.join(" ") || "0 месяцев";
}

/** Верхняя граница срока: 360 -> "до 30 лет", 12 -> "до 1 года", 18 -> "до 18 месяцев" */
export function formatTermLimit(months: number): string {
  if (months % 12 === 0) {
    const years = months / 12;
    return `до ${years} ${plural(years, "года", "лет", "лет")}`;
  }
  return `до ${months} ${plural(months, "месяца", "месяцев", "месяцев")}`;
}
