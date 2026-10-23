import { SliderField } from "@/shared/ui";
import { formatCompactCurrency } from "@/shared/lib/format";

interface AmountSliderProps {
  id: string;
  value: number;
  min: number;
  max: number;
  error?: string;
  onChange: (amount: number) => void;
}

const STEPS = [1_000, 5_000, 10_000, 50_000, 100_000];

/**
 * Шаг ползунка под диапазон продукта: ~300 делений на всю шкалу.
 * Все границы продуктов кратны выбранному шагу, поэтому и минимум, и максимум достижимы.
 */
export function amountStep(min: number, max: number): number {
  const target = (max - min) / 300;
  return STEPS.find((s) => s >= target && min % s === 0 && max % s === 0) ?? 1;
}

/** Сумма кредита: поле «1 000 000 ₽» + ползунок */
export function AmountSlider({ id, value, min, max, error, onChange }: AmountSliderProps) {
  return (
    <SliderField
      id={id}
      label="Сумма кредита"
      value={value}
      min={min}
      max={max}
      step={amountStep(min, max)}
      suffix="₽"
      minLabel={formatCompactCurrency(min)}
      maxLabel={formatCompactCurrency(max)}
      sliderLabel="Сумма кредита, ползунок"
      error={error}
      onChange={onChange}
    />
  );
}
