import type { ReactNode } from "react";
import { CircleAlert } from "lucide-react";

interface FieldProps {
  id: string;
  label: string;
  /** Текст справа от подписи (например, срок словами) */
  aside?: ReactNode;
  hint?: ReactNode;
  error?: string;
  children: ReactNode;
}

/** Подпись + контрол + подсказка/ошибка. Ошибка заменяет подсказку. */
export function Field({ id, label, aside, hint, error, children }: FieldProps) {
  return (
    <div>
      <div className="mb-2 flex items-baseline justify-between gap-3">
        <label htmlFor={id} className="text-sm font-medium text-ink">
          {label}
        </label>
        {aside && <span className="text-sm text-muted">{aside}</span>}
      </div>
      {children}
      {error ? (
        <FieldError id={errorId(id)}>{error}</FieldError>
      ) : (
        hint && (
          <p id={hintId(id)} className="mt-2 text-xs text-muted">
            {hint}
          </p>
        )
      )}
    </div>
  );
}

export function FieldError({ id, children }: { id?: string; children: ReactNode }) {
  return (
    <p id={id} className="mt-2 flex items-start gap-1.5 text-sm text-danger">
      <CircleAlert className="mt-0.5 h-4 w-4 shrink-0" aria-hidden />
      <span>{children}</span>
    </p>
  );
}

export const errorId = (id: string) => `${id}-error`;
export const hintId = (id: string) => `${id}-hint`;

/** Значение aria-describedby для поля: ошибка важнее подсказки */
export function describedBy(id: string, opts: { error?: string; hint?: boolean }): string | undefined {
  if (opts.error) return errorId(id);
  if (opts.hint) return hintId(id);
  return undefined;
}

/** Базовые классы текстового поля */
export function inputClassName(invalid: boolean, extra = ""): string {
  return [
    "block w-full rounded-2xl border bg-surface px-4 py-3.5 text-base text-ink",
    "placeholder:text-muted/70 transition-[border-color,box-shadow] duration-150",
    "focus:outline-none focus:ring-4",
    invalid
      ? "border-danger focus:border-danger focus:ring-danger/15"
      : "border-line hover:border-line-strong focus:border-brand focus:ring-brand/15",
    extra,
  ].join(" ");
}
