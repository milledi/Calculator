/** Знак сервиса: стилизованный «%» */
export function LogoMark({ className = "h-9 w-9" }: { className?: string }) {
  return (
    <svg viewBox="0 0 32 32" className={className} aria-hidden>
      <rect width="32" height="32" rx="9" fill="rgb(var(--brand))" />
      <path d="M9 22 L23 10" stroke="rgb(var(--highlight))" strokeWidth="3" strokeLinecap="round" />
      <circle cx="10.5" cy="11.5" r="2.5" fill="rgb(var(--on-brand))" />
      <circle cx="21.5" cy="20.5" r="2.5" fill="rgb(var(--on-brand))" />
    </svg>
  );
}
