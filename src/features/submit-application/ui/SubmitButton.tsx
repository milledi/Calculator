import { ArrowRight, LoaderCircle } from "lucide-react";

interface SubmitButtonProps {
  /** id формы, которую отправляет кнопка (кнопка стоит вне формы, в карточке расчёта) */
  form: string;
  loading: boolean;
}

export function SubmitButton({ form, loading }: SubmitButtonProps) {
  return (
    <button
      type="submit"
      form={form}
      disabled={loading}
      aria-busy={loading}
      className={[
        "group flex min-h-14 w-full items-center justify-center gap-2 rounded-2xl px-6 py-3.5 text-base font-semibold",
        "bg-highlight text-highlight-on transition-[filter,transform] duration-150",
        "hover:brightness-105 active:scale-[0.99]",
        "focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-highlight/40",
        "disabled:cursor-wait disabled:opacity-80",
      ].join(" ")}
    >
      {loading ? (
        <>
          <LoaderCircle className="h-5 w-5 animate-spin" aria-hidden />
          Отправляем…
        </>
      ) : (
        <>
          Отправить заявку
          <ArrowRight
            className="h-5 w-5 transition-transform duration-150 group-hover:translate-x-0.5"
            aria-hidden
          />
        </>
      )}
    </button>
  );
}
