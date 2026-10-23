import { Calculator } from "lucide-react";
import { LoanCalculator } from "@/widgets/loan-calculator/ui/LoanCalculator";
import { LogoMark } from "@/shared/ui";

export function CalculatorPage() {
  return (
    <div className="flex min-h-screen flex-col pb-24 lg:pb-0">
      <header className="border-b border-line">
        <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6">
          <a
            href="/"
            className="flex items-center gap-3 rounded-xl font-semibold text-ink focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand/20"
          >
            <LogoMark />
            <span>Кредитный калькулятор</span>
          </a>
          <p className="hidden items-center gap-2 text-sm text-muted sm:flex">
            <Calculator className="h-4 w-4 text-brand" aria-hidden />
            Расчёт без регистрации
          </p>
        </div>
      </header>

      <main className="mx-auto w-full max-w-6xl flex-1 px-4 sm:px-6">
        <div className="max-w-3xl pb-8 pt-10 sm:pb-10 sm:pt-14">
          <h1 className="text-balance text-4xl font-semibold leading-[1.1] tracking-tight text-ink sm:text-5xl">
            Рассчитайте кредит и&nbsp;отправьте заявку онлайн
          </h1>
          <p className="mt-4 max-w-2xl text-pretty text-lg leading-relaxed text-muted">
            Выберите продукт, задайте сумму и срок — платёж пересчитывается сразу. Когда условия
            подойдут, заполните пару полей и отправьте заявку.
          </p>
        </div>

        <LoanCalculator />
      </main>

      <footer className="mt-16 border-t border-line">
        <div className="mx-auto max-w-6xl px-4 py-8 text-sm leading-relaxed text-muted sm:px-6">
          Расчёт предварительный и не является публичной офертой. Платежи аннуитетные, ставка фиксирована
          на весь срок. Итоговые условия определяются после рассмотрения заявки.
        </div>
      </footer>
    </div>
  );
}
