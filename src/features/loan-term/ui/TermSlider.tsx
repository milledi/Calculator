import { SliderField } from "@/shared/ui";
import { formatTerm } from "@/shared/lib/term";

interface TermSliderProps {
  id: string;
  value: number;
  min: number;
  max: number;
  error?: string;
  onChange: (termMonths: number) => void;
}

/** Срок кредита в месяцах: поле + ползунок, справа — срок словами */
export function TermSlider({ id, value, min, max, error, onChange }: TermSliderProps) {
  return (
    <SliderField
      id={id}
      label="Срок кредита"
      aside={value > 0 ? formatTerm(value) : undefined}
      value={value}
      min={min}
      max={max}
      step={1}
      suffix="мес."
      minLabel={formatTerm(min)}
      maxLabel={formatTerm(max)}
      sliderLabel="Срок кредита, ползунок"
      error={error}
      onChange={onChange}
    />
  );
}
