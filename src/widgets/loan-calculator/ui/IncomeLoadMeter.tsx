import { CircleCheck, TriangleAlert } from "lucide-react";
import { MAX_PAYMENT_TO_INCOME_RATIO } from "@/shared/config/loanProducts";
import { formatCurrency } from "@/shared/lib/format";

interface IncomeLoadMeterProps {
  monthlyPayment: number;
  monthlyIncome: number;
  requiredIncome: number;
}

/** Доля платежа в доходе со шкалой 0–100% и отметкой лимита 50% */
export function IncomeLoadMeter({ monthlyPayment, monthlyIncome, requiredIncome }: IncomeLoadMeterProps) {
  if (monthlyIncome <= 0) {
    return (
      <p className="rounded-2xl border border-dashed border-panel-line px-4 py-3 text-sm text-panel-muted">
        Укажите доход — покажем, какую часть бюджета займёт платёж.
      </p>
    );
  }

  const load = (monthlyPayment / monthlyIncome) * 100;
  const limit = MAX_PAYMENT_TO_INCOME_RATIO * 100;
  const ok = load <= limit;
  // Округляем вверх, чтобы 50,3% не показывалось как «50%» рядом с предупреждением
  const shown = Math.ceil(load);

  return (
    <div>
      <div className="flex items-baseline justify-between text-sm">
        <span className="text-panel-muted">Нагрузка на доход</span>
        <span className="font-semibold tabular-nums text-panel-ink">{shown}%</span>
      </div>
      <div
        role="meter"
        aria-label="Доля платежа в доходе"
        aria-valuemin={0}
        aria-valuemax={100}
        aria-valuenow={Math.min(shown, 100)}
        aria-valuetext={`${shown}% дохода, допустимо до ${limit}%`}
        className={`relative mt-2 h-2 rounded-full ${ok ? "bg-chart-good/25" : "bg-chart-warn/25"}`}
      >
        <div
          className={`h-full rounded-full ${ok ? "bg-chart-good" : "bg-chart-warn"}`}
          style={{ width: `${Math.min(load, 100)}%` }}
        />
        <div
          aria-hidden
          className="absolute -bottom-1 -top-1 w-0.5 -translate-x-1/2 rounded-full bg-panel-ink"
          style={{ left: `${limit}%` }}
        />
      </div>
      <div aria-hidden className="relative mt-1 h-4 text-[11px] text-panel-muted">
        <span className="absolute -translate-x-1/2" style={{ left: `${limit}%` }}>
          лимит {limit}%
        </span>
      </div>
      <p className="mt-1 flex items-start gap-2 text-sm text-panel-ink">
        {ok ? (
          <>
            <CircleCheck className="mt-0.5 h-4 w-4 shrink-0 text-chart-good" aria-hidden />
            Платёж укладывается в половину дохода
          </>
        ) : (
          <>
            <TriangleAlert className="mt-0.5 h-4 w-4 shrink-0 text-chart-warn" aria-hidden />
            Платёж больше половины дохода — нужен доход от {formatCurrency(requiredIncome)}
          </>
        )}
      </p>
    </div>
  );
}
