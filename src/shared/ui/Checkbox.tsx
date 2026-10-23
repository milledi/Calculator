import type { ReactNode } from "react";
import { Check } from "lucide-react";
import { FieldError, errorId } from "./Field";

interface CheckboxProps {
  id: string;
  checked: boolean;
  error?: string;
  onChange: (checked: boolean) => void;
  children: ReactNode;
}

/** Чекбокс с крупной зоной нажатия: настоящий input скрыт, рисуем свой квадрат */
export function Checkbox({ id, checked, error, onChange, children }: CheckboxProps) {
  return (
    <div>
      <label htmlFor={id} className="flex cursor-pointer items-start gap-3 py-1">
        <input
          id={id}
          type="checkbox"
          checked={checked}
          onChange={(e) => onChange(e.target.checked)}
          aria-invalid={Boolean(error)}
          aria-describedby={error ? errorId(id) : undefined}
          className="peer sr-only"
        />
        <span
          aria-hidden
          className={[
            "mt-px grid h-6 w-6 shrink-0 place-items-center rounded-lg border-2 transition-colors duration-150",
            "peer-focus-visible:ring-4 peer-focus-visible:ring-brand/20",
            checked
              ? "border-brand bg-brand text-brand-on"
              : error
                ? "border-danger bg-surface"
                : "border-line-strong bg-surface hover:border-muted",
          ].join(" ")}
        >
          {checked && <Check className="h-4 w-4" strokeWidth={3} />}
        </span>
        <span className="text-sm leading-6 text-ink">{children}</span>
      </label>
      {error && (
        <div className="pl-9">
          <FieldError id={errorId(id)}>{error}</FieldError>
        </div>
      )}
    </div>
  );
}
