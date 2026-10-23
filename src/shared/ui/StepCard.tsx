import { useId, type ReactNode } from "react";

interface StepCardProps {
  step: number;
  title: string;
  description?: string;
  children: ReactNode;
}

/** Карточка шага формы с номером и заголовком */
export function StepCard({ step, title, description, children }: StepCardProps) {
  const titleId = useId();
  return (
    <section
      aria-labelledby={titleId}
      className="rounded-4xl border border-line bg-surface p-5 shadow-card sm:p-8"
    >
      <header className="mb-6 flex items-start gap-3.5">
        <span
          aria-hidden
          className="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-brand-soft text-sm font-semibold text-brand"
        >
          {step}
        </span>
        <div>
          <h2 id={titleId} className="text-lg font-semibold leading-8 text-ink">
            {title}
          </h2>
          {description && <p className="text-sm text-muted">{description}</p>}
        </div>
      </header>
      {children}
    </section>
  );
}
