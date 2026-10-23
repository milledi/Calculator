import type { CSSProperties, ReactNode } from "react";
import { formatNumber, parseFormattedNumber } from "@/shared/lib/format";
import { FieldError, errorId } from "./Field";

interface SliderFieldProps {
  id: string;
  label: string;
  value: number;
  min: number;
  max: number;
  step: number;
  /** Единица измерения справа в поле: «₽», «мес.» */
  suffix: string;
  /** Текст справа от подписи */
  aside?: ReactNode;
  minLabel: string;
  maxLabel: string;
  sliderLabel: string;
  error?: string;
  onChange: (value: number) => void;
}

/**
 * Числовое поле с ползунком на нижней границе.
 * Ввод с клавиатуры может выйти за границы — при потере фокуса значение прижимается к ним.
 */
export function SliderField({
  id,
  label,
  value,
  min,
  max,
  step,
  suffix,
  aside,
  minLabel,
  maxLabel,
  sliderLabel,
  error,
  onChange,
}: SliderFieldProps) {
  const clamped = clamp(value, min, max);
  const fill = max > min ? ((clamped - min) / (max - min)) * 100 : 0;

  return (
    <div>
      <div className="mb-2 flex items-baseline justify-between gap-3">
        <label htmlFor={id} className="text-sm font-medium text-ink">
          {label}
        </label>
        {aside && <span className="text-sm text-muted">{aside}</span>}
      </div>

      <div
        className={[
          "relative rounded-2xl border bg-surface transition-[border-color,box-shadow] duration-150",
          "focus-within:ring-4",
          error
            ? "border-danger focus-within:ring-danger/15"
            : "border-line hover:border-line-strong focus-within:border-brand focus-within:ring-brand/15",
        ].join(" ")}
      >
        <input
          id={id}
          type="text"
          inputMode="numeric"
          autoComplete="off"
          value={value === 0 ? "" : formatNumber(value)}
          aria-invalid={Boolean(error)}
          aria-describedby={error ? errorId(id) : undefined}
          onChange={(e) => {
            const parsed = parseFormattedNumber(e.target.value);
            onChange(Number.isNaN(parsed) ? 0 : parsed);
          }}
          onBlur={() => {
            if (value !== clamped) onChange(clamped);
          }}
          className="block w-full rounded-2xl bg-transparent py-4 pl-4 pr-16 text-2xl font-semibold tracking-tight text-ink focus:outline-none sm:text-[1.75rem]"
        />
        <span
          aria-hidden
          className="pointer-events-none absolute inset-y-0 right-4 flex items-center pb-1 text-lg font-medium text-muted"
        >
          {suffix}
        </span>
        <div className="absolute inset-x-4 -bottom-[14px]">
          <input
            type="range"
            min={min}
            max={max}
            step={step}
            value={clamped}
            onChange={(e) => onChange(Number(e.target.value))}
            aria-label={sliderLabel}
            aria-valuetext={`${formatNumber(clamped)} ${suffix}`}
            style={{ "--fill": `${fill}%` } as CSSProperties}
            className="range"
          />
        </div>
      </div>

      <div className="mt-5 flex justify-between text-xs text-muted">
        <span>{minLabel}</span>
        <span>{maxLabel}</span>
      </div>
      {error && <FieldError id={errorId(id)}>{error}</FieldError>}
    </div>
  );
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}
