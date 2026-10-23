import { useId } from "react";
import { Car, Check, House, ShoppingBag, Zap, type LucideIcon } from "lucide-react";
import { LOAN_PRODUCTS, LOAN_TYPE_IDS, type LoanTypeId } from "@/shared/config/loanProducts";
import { formatCompactCurrency, formatRate } from "@/shared/lib/format";
import { formatTermLimit } from "@/shared/lib/term";

interface LoanTypePickerProps {
  value: LoanTypeId;
  onChange: (type: LoanTypeId) => void;
}

const PRODUCT_ICONS: Record<LoanTypeId, LucideIcon> = {
  mortgage: House,
  auto: Car,
  consumer: ShoppingBag,
  payday: Zap,
};

/** Выбор продукта карточками. Внутри — обычные radio, поэтому работают стрелки и Tab. */
export function LoanTypePicker({ value, onChange }: LoanTypePickerProps) {
  const name = useId();

  return (
    <fieldset>
      <legend className="sr-only">Тип кредита</legend>
      <div className="grid gap-3 sm:grid-cols-2">
        {LOAN_TYPE_IDS.map((id) => {
          const product = LOAN_PRODUCTS[id];
          const Icon = PRODUCT_ICONS[id];
          const selected = id === value;

          return (
            <label
              key={id}
              className={[
                "flex cursor-pointer items-center gap-4 rounded-3xl border p-4 transition-[border-color,background-color,box-shadow] duration-200",
                "has-[:focus-visible]:ring-4 has-[:focus-visible]:ring-brand/20",
                selected
                  ? "border-brand bg-brand-soft/60 shadow-[inset_0_0_0_1px_rgb(var(--brand))]"
                  : "border-line bg-surface hover:border-line-strong hover:bg-subtle/40",
              ].join(" ")}
            >
              <input
                type="radio"
                name={name}
                value={id}
                checked={selected}
                onChange={() => onChange(id)}
                className="sr-only"
              />
              <span
                className={[
                  "relative grid h-12 w-12 shrink-0 place-items-center rounded-2xl transition-colors duration-200",
                  selected ? "bg-brand text-brand-on" : "bg-subtle text-ink",
                ].join(" ")}
              >
                <Icon className="h-5 w-5" aria-hidden />
                {selected && (
                  <span
                    aria-hidden
                    className="absolute -right-1.5 -top-1.5 grid h-5 w-5 place-items-center rounded-full border-2 border-surface bg-highlight text-highlight-on"
                  >
                    <Check className="h-3 w-3" strokeWidth={3.5} />
                  </span>
                )}
              </span>

              <span className="min-w-0 flex-1">
                <span className="block font-semibold leading-snug text-ink">{product.title}</span>
                {/* На телефоне — одной строкой через точку, в две колонки — двумя строками */}
                <span className="mt-0.5 block text-sm text-muted">
                  <span className="whitespace-nowrap">до {formatCompactCurrency(product.maxAmount)}</span>
                  <span className="sm:hidden"> · </span>
                  <span className="whitespace-nowrap sm:block">{formatTermLimit(product.maxTermMonths)}</span>
                </span>
              </span>

              <span className="shrink-0 text-right">
                <span className="block text-xl font-semibold tracking-tight text-ink">
                  {formatRate(product.annualRate)}
                </span>
                <span className="block text-xs text-muted">годовых</span>
              </span>
            </label>
          );
        })}
      </div>
    </fieldset>
  );
}
