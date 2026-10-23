import { forwardRef, type ReactNode } from "react";
import type { LoanResult } from "@/entities/loan";
import type { LoanProduct } from "@/shared/config/loanProducts";
import { formatCurrency, formatNumber, formatRate } from "@/shared/lib/format";
import { formatTerm, plural } from "@/shared/lib/term";
import { PaymentBreakdown } from "./PaymentBreakdown";
import { IncomeLoadMeter } from "./IncomeLoadMeter";

interface LoanSummaryProps {
  product: LoanProduct;
  amount: number;
  termMonths: number;
  monthlyIncome: number;
  result: LoanResult;
  /** Кнопка отправки и статусы */
  children: ReactNode;
}

/** Тёмная карточка с итогом расчёта */
export const LoanSummary = forwardRef<HTMLElement, LoanSummaryProps>(function LoanSummary(
  { product, amount, termMonths, monthlyIncome, result, children },
  ref,
) {
  const rows = [
    {
      label: "Срок",
      value: `${formatTerm(termMonths)} · ${formatNumber(result.paymentsCount)} ${plural(result.paymentsCount, "платёж", "платежа", "платежей")}`,
    },
    { label: "Ставка", value: `${formatRate(product.annualRate)} годовых` },
    { label: "Всего выплат", value: formatCurrency(result.totalRepaid) },
    {
      label: "Нужный доход",
      value: `от ${formatCurrency(result.requiredIncome)}`,
      testId: "required-income",
    },
  ];

  return (
    <aside
      ref={ref}
      id="summary"
      aria-label="Результат расчёта"
      className="scroll-mt-6 rounded-4xl border border-panel-line bg-panel p-6 text-panel-ink shadow-card sm:p-8 lg:sticky lg:top-6"
    >
      <div className="flex items-center justify-between gap-3">
        <h2 className="text-sm font-medium text-panel-muted">Ваш расчёт</h2>
        <span className="rounded-full border border-panel-line px-3 py-1 text-xs font-medium text-panel-ink">
          {product.title}
        </span>
      </div>

      <p className="mt-6 text-sm text-panel-muted">Ежемесячный платёж</p>
      <p
        className="mt-1 text-[2.75rem] font-semibold leading-none tracking-tight sm:text-5xl"
        data-testid="monthly-payment"
      >
        {formatCurrency(result.monthlyPayment)}
      </p>

      <div className="mt-8">
        <PaymentBreakdown principal={amount} interest={result.totalInterest} />
      </div>

      <dl className="mt-6 divide-y divide-panel-line border-y border-panel-line text-sm">
        {rows.map((row) => (
          <div key={row.label} className="flex items-baseline justify-between gap-4 py-3">
            <dt className="text-panel-muted">{row.label}</dt>
            <dd className="text-right font-medium" data-testid={row.testId}>
              {row.value}
            </dd>
          </div>
        ))}
      </dl>

      <div className="mt-6">
        <IncomeLoadMeter
          monthlyPayment={result.monthlyPayment}
          monthlyIncome={monthlyIncome}
          requiredIncome={result.requiredIncome}
        />
      </div>

      <div className="mt-8">{children}</div>
    </aside>
  );
});
