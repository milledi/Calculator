import { ArrowDown } from "lucide-react";
import { formatCurrency } from "@/shared/lib/format";

interface MobileSummaryBarProps {
  monthlyPayment: number;
  visible: boolean;
}

/** Нижняя панель на телефонах: платёж всегда на виду, пока карточка расчёта за экраном */
export function MobileSummaryBar({ monthlyPayment, visible }: MobileSummaryBarProps) {
  return (
    <div
      aria-hidden={!visible}
      className={[
        "fixed inset-x-0 bottom-0 z-20 border-t border-panel-line bg-panel/95 text-panel-ink backdrop-blur lg:hidden",
        "pb-[max(0.75rem,env(safe-area-inset-bottom))] pt-3 transition-transform duration-300 ease-out",
        visible ? "translate-y-0" : "pointer-events-none translate-y-full",
      ].join(" ")}
    >
      <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 sm:px-6">
        <div>
          <p className="text-xs text-panel-muted">Платёж в месяц</p>
          <p className="text-xl font-semibold tracking-tight">{formatCurrency(monthlyPayment)}</p>
        </div>
        <a
          href="#summary"
          tabIndex={visible ? 0 : -1}
          className="flex min-h-11 items-center gap-1.5 rounded-xl bg-highlight px-4 text-sm font-semibold text-highlight-on focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-highlight/40"
        >
          К заявке
          <ArrowDown className="h-4 w-4" aria-hidden />
        </a>
      </div>
    </div>
  );
}
