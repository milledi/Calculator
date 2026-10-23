import { formatCurrency } from "@/shared/lib/format";

interface PaymentBreakdownProps {
  principal: number;
  interest: number;
}

/** Из чего состоят выплаты: тело кредита и проценты, полоса + легенда с числами */
export function PaymentBreakdown({ principal, interest }: PaymentBreakdownProps) {
  const total = principal + interest;
  const principalShare = total > 0 ? Math.round((principal / total) * 100) : 0;
  const interestShare = 100 - principalShare;

  const items = [
    { key: "principal", label: "Сумма кредита", value: principal, share: principalShare, swatch: "bg-chart-principal" },
    { key: "interest", label: "Переплата (проценты)", value: interest, share: interestShare, swatch: "bg-chart-interest" },
  ];

  return (
    <figure>
      <figcaption className="text-sm text-panel-muted">Структура выплат</figcaption>
      <div
        role="img"
        aria-label={`Сумма кредита — ${principalShare}% выплат, проценты — ${interestShare}%`}
        className="mt-3 flex h-3 gap-0.5"
      >
        <div
          className="rounded-l-[4px] bg-chart-principal"
          style={{ flexGrow: principal, flexBasis: 0 }}
          title={`Сумма кредита: ${formatCurrency(principal)}`}
        />
        <div
          className="rounded-r-[4px] bg-chart-interest"
          style={{ flexGrow: interest, flexBasis: 0 }}
          title={`Проценты: ${formatCurrency(interest)}`}
        />
      </div>
      <ul className="mt-3 space-y-1.5 text-sm">
        {items.map((item) => (
          <li key={item.key} className="flex items-center justify-between gap-3">
            <span className="flex items-center gap-2 text-panel-muted">
              <span aria-hidden className={`h-2.5 w-2.5 rounded-[3px] ${item.swatch}`} />
              {item.label}
            </span>
            <span className="whitespace-nowrap text-panel-ink">
              {formatCurrency(item.value)}
              <span className="ml-2 inline-block w-10 text-right text-panel-muted">{item.share}%</span>
            </span>
          </li>
        ))}
      </ul>
    </figure>
  );
}
