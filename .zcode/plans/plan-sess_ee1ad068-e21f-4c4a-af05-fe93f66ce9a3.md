# Кредитный калькулятор — React + TS + Tailwind + Vite + Jest (архитектура FSD)

Новый проект с нулём в `bis-225-calc`, структура по методологии Feature-Sliced Design (FSD).

## Стек
- Vite + React + TypeScript
- TailwindCSS
- Zod — валидация
- Jest (ts-jest) — тесты логики кредитования и схем

## FSD-структура
```
package.json, vite.config.ts, tailwind.config.js, postcss.config.js, tsconfig.json
jest.config.ts, setupTests.ts
index.html
src/
  App.tsx                      — корневой роутинг/композиция
  index.css
  pages/
    calculator/
      ui/CalculatorPage.tsx    — страница-композиция слоёв
  widgets/
    loan-calculator/
      ui/LoanCalculator.tsx    — объединённая форма + результат
      ui/LoanCalculator.module... (Tailwind, без css-модулей)
      model/store.ts            — состояние формы (useState/useReducer)
  features/
    loan-type-select/ui/LoanTypeChips.tsx   — чипсы типов кредита
    loan-amount/ui/AmountSlider.tsx          — сумма: range + input
    loan-term/ui/TermSlider.tsx              — срок: range + input
    submit-application/ui/SubmitButton.tsx   — кнопка «Оставить заявку» (alert)
  entities/
    loan/
      model/types.ts      — типы LoanType, LoanParams, LoanResult
      model/credit.ts     — аннуитетный расчёт, требуемая зарплата
      lib/tests: __tests__/credit.test.ts
    applicant/
      model/schema.ts     — zod-схема (ФИО, гражданство, 152-ФЗ, доход)
  shared/
    lib/format.ts         — «1 000 000» форматирование/парсинг
    lib/__tests__/format.test.ts
    ui/ (при необходимости Chip, Checkbox)
    config/loanProducts.ts — ставки: ипотека 18%, автокредит 23.5%, потребкредит 22.2%, до зарплаты 50%
  shared/api — не нужен (заявка пока alert)
```
Правила FSD соблюдаем: слои app → pages → widgets → features → entities → shared; импорты только строго вниз по слоям (проверяется структурой, public API через index.ts каждого сегмента).

## Функциональность
- Ставка по типу кредита (чипсы): ипотека 18%, автокредит 23.5%, потребительский 22.2%, деньги до зарплаты 50%.
- Сумма и срок: слайдер + input, синхронизация, числа с пробелами (1 000 000).
- Аннуитет: `P = S × i × (1+i)^n / ((1+i)^n − 1)`, i = годовая/12.
- Результат: месячный платёж, количество платежей, требуемая зарплата = платёж × 2 (платёж ≤ 50% дохода).
- Поля: ФИО (zod: буквы/пробелы/дефис, min 2), чекбокс гражданства, чекбокс согласия с политикой (152-ФЗ, ссылка), поле дохода с проверкой достаточности.
- Заявка: кнопка активна только при валидной zod-схеме → alert со сводкой.

## Безопасность (XSS)
- Никакого dangerouslySetInnerHTML / eval / инлайн-JS.
- Все входные данные — через zod перед использованием; вывод только текстом в JSX (React экранирует).

## Тесты (Jest)
- `entities/loan/__tests__/credit.test.ts` — таблица «вход → результат»: 4 ставки, точные значения платежей (например 1 000 000 ₽ / 18% / 60 мес ≈ 25 393.42 ₽), требуемая зарплата, крайний случай срок 1 мес.
- `entities/applicant/__tests__/schema.test.ts` — невалидные ФИО, отрицательная сумма, непоставленные чекбоксы → ошибки zod.
- `shared/lib/__tests__/format.test.ts` — «1000000 → 1 000 000» и обратный парсинг.

## Порядок работ
1. Конфиги (vite, tailwind, ts, jest) + index.html.
2. Слой shared (format, ставки) → entities (типы, расчёт, zod) → features (UI-примитивы формы) → widgets → pages → App.
3. Тесты, `npm test`.
4. `npm run build`, проверка dev-сервера.

## Запуск
`npm run dev`, `npm test`, `npm run build`.