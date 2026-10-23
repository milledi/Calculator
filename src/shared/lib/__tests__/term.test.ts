import { formatTerm, formatTermLimit, plural } from "@/shared/lib/term";

const words = (n: number) => plural(n, "год", "года", "лет");

describe("plural", () => {
  it.each([
    [1, "год"],
    [2, "года"],
    [4, "года"],
    [5, "лет"],
    [11, "лет"],
    [12, "лет"],
    [14, "лет"],
    [21, "год"],
    [22, "года"],
    [30, "лет"],
  ])("%i -> %s", (n, expected) => {
    expect(words(n)).toBe(expected);
  });
});

describe("formatTerm", () => {
  it.each([
    [120, "10 лет"],
    [12, "1 год"],
    [18, "1 год 6 месяцев"],
    [25, "2 года 1 месяц"],
    [3, "3 месяца"],
    [0, "0 месяцев"],
  ])("%i мес -> %s", (months, expected) => {
    expect(formatTerm(months)).toBe(expected);
  });
});

describe("formatTermLimit", () => {
  it.each([
    [360, "до 30 лет"],
    [84, "до 7 лет"],
    [12, "до 1 года"],
    [252, "до 21 года"],
    [18, "до 18 месяцев"],
  ])("%i мес -> %s", (months, expected) => {
    expect(formatTermLimit(months)).toBe(expected);
  });
});
