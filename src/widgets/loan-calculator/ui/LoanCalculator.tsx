import { useMemo, useRef, useState, type FormEvent } from "react";
import { CircleAlert, CircleCheck, RotateCcw } from "lucide-react";
import { LoanTypePicker } from "@/features/loan-type-select/ui/LoanTypePicker";
import { AmountSlider } from "@/features/loan-amount/ui/AmountSlider";
import { TermSlider } from "@/features/loan-term/ui/TermSlider";
import { SubmitButton } from "@/features/submit-application/ui/SubmitButton";
import { useLoanCalculatorPush } from "@/features/loan-application/model/useLoanCalculatorPush";
import { loanApplicationSchema, type LoanApplication } from "@/entities/applicant";
import { calculateLoan } from "@/entities/loan";
import { LOAN_PRODUCTS, type LoanTypeId } from "@/shared/config/loanProducts";
import { formatNumber, parseFormattedNumber } from "@/shared/lib/format";
import { useInView } from "@/shared/lib/useInView";
import { Checkbox, Field, StepCard, describedBy, inputClassName } from "@/shared/ui";
import { LoanSummary } from "./LoanSummary";
import { MobileSummaryBar } from "./MobileSummaryBar";

interface FormDraft {
  loanType: LoanTypeId;
  amount: number;
  termMonths: number;
  fullName: string;
  monthlyIncome: number;
  hasCitizenship: boolean;
  consentGiven: boolean;
}

type FieldName = keyof LoanApplication;

const INITIAL_DRAFT: FormDraft = {
  loanType: "mortgage",
  amount: 5_000_000,
  termMonths: 120,
  fullName: "",
  monthlyIncome: 0,
  hasCitizenship: false,
  consentGiven: false,
};

const FORM_ID = "loan-application";

/** id элементов в порядке следования на странице — к первому ошибочному переводим фокус */
const FIELD_IDS: [FieldName, string][] = [
  ["amount", "loan-amount"],
  ["termMonths", "loan-term"],
  ["fullName", "full-name"],
  ["monthlyIncome", "monthly-income"],
  ["hasCitizenship", "has-citizenship"],
  ["consentGiven", "consent-given"],
];
const fieldId = (field: FieldName) => FIELD_IDS.find(([name]) => name === field)![1];

export function LoanCalculator() {
  const [draft, setDraft] = useState<FormDraft>(INITIAL_DRAFT);
  const [touched, setTouched] = useState<Partial<Record<FieldName, boolean>>>({});
  const [submitAttempted, setSubmitAttempted] = useState(false);

  const product = LOAN_PRODUCTS[draft.loanType];
  const amount = clamp(draft.amount, product.minAmount, product.maxAmount);
  const termMonths = clamp(draft.termMonths, product.minTermMonths, product.maxTermMonths);

  const result = useMemo(
    () => calculateLoan({ loanType: draft.loanType, amount, termMonths, annualRate: product.annualRate }),
    [draft.loanType, amount, termMonths, product.annualRate],
  );

  const validation = loanApplicationSchema.safeParse(draft);
  const isValid = validation.success;

  const errors = useMemo(() => {
    const map: Partial<Record<FieldName, string>> = {};
    if (validation.success) return map;
    for (const issue of validation.error.issues) {
      const key = issue.path[0] as FieldName;
      if (!map[key]) map[key] = issue.message;
    }
    return map;
  }, [validation]);

  const showError = (field: FieldName) =>
    touched[field] || submitAttempted ? errors[field] : undefined;

  const setField = <K extends keyof FormDraft>(field: K, value: FormDraft[K]) =>
    setDraft((prev) => ({ ...prev, [field]: value }));

  const touch = (field: FieldName) => setTouched((t) => (t[field] ? t : { ...t, [field]: true }));

  const { push, status, error: pushError, applicationId, reset: resetPush } = useLoanCalculatorPush();

  const summaryRef = useRef<HTMLElement>(null);
  const summaryInView = useInView(summaryRef);

  const handleSubmit = (e?: FormEvent) => {
    e?.preventDefault();
    if (status === "submitting") return;

    if (!validation.success) {
      setSubmitAttempted(true);
      const first = FIELD_IDS.find(([name]) => errors[name]);
      if (first) {
        const el = document.getElementById(first[1]);
        el?.focus({ preventScroll: true });
        el?.scrollIntoView({ behavior: "smooth", block: "center" });
      }
      return;
    }
    void push(validation.data);
  };

  const startOver = () => {
    setDraft(INITIAL_DRAFT);
    setTouched({});
    setSubmitAttempted(false);
    resetPush();
    window.scrollTo({ top: 0, behavior: "smooth" });
  };

  return (
    <>
      <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_400px] lg:items-start xl:gap-8">
        <form id={FORM_ID} className="space-y-6" onSubmit={handleSubmit} noValidate>
          <StepCard step={1} title="Выберите кредит" description="Ставка фиксированная на весь срок">
            <LoanTypePicker
              value={draft.loanType}
              onChange={(loanType) => {
                const p = LOAN_PRODUCTS[loanType];
                setDraft((prev) => ({
                  ...prev,
                  loanType,
                  amount: clamp(prev.amount, p.minAmount, p.maxAmount),
                  termMonths: clamp(prev.termMonths, p.minTermMonths, p.maxTermMonths),
                }));
              }}
            />
          </StepCard>

          <StepCard step={2} title="Сумма и срок" description="Двигайте ползунок или введите число">
            <div className="space-y-8">
              <AmountSlider
                id={fieldId("amount")}
                value={draft.amount}
                min={product.minAmount}
                max={product.maxAmount}
                error={showError("amount")}
                onChange={(value) => setField("amount", value)}
              />
              <TermSlider
                id={fieldId("termMonths")}
                value={draft.termMonths}
                min={product.minTermMonths}
                max={product.maxTermMonths}
                error={showError("termMonths")}
                onChange={(value) => setField("termMonths", value)}
              />
            </div>
          </StepCard>

          <StepCard step={3} title="О вас" description="Нужно только для заявки — расчёт работает и без этого">
            <div className="space-y-5">
              <div className="grid gap-5 sm:grid-cols-2">
                <Field id={fieldId("fullName")} label="ФИО" error={showError("fullName")}>
                  <input
                    id={fieldId("fullName")}
                    type="text"
                    autoComplete="name"
                    value={draft.fullName}
                    onBlur={() => touch("fullName")}
                    onChange={(e) => setField("fullName", e.target.value)}
                    placeholder="Иванов Иван Иванович"
                    aria-invalid={Boolean(showError("fullName"))}
                    aria-describedby={describedBy(fieldId("fullName"), { error: showError("fullName") })}
                    className={inputClassName(Boolean(showError("fullName")))}
                  />
                </Field>

                <Field
                  id={fieldId("monthlyIncome")}
                  label="Ежемесячный доход"
                  hint="Подтверждённый, после вычета налогов"
                  error={showError("monthlyIncome")}
                >
                  <div className="relative">
                    <input
                      id={fieldId("monthlyIncome")}
                      type="text"
                      inputMode="numeric"
                      autoComplete="off"
                      value={draft.monthlyIncome === 0 ? "" : formatNumber(draft.monthlyIncome)}
                      onBlur={() => touch("monthlyIncome")}
                      onChange={(e) => {
                        const parsed = parseFormattedNumber(e.target.value);
                        setField("monthlyIncome", Number.isNaN(parsed) ? 0 : parsed);
                      }}
                      placeholder="100 000"
                      aria-invalid={Boolean(showError("monthlyIncome"))}
                      aria-describedby={describedBy(fieldId("monthlyIncome"), {
                        error: showError("monthlyIncome"),
                        hint: true,
                      })}
                      className={inputClassName(Boolean(showError("monthlyIncome")), "pr-10")}
                    />
                    <span
                      aria-hidden
                      className="pointer-events-none absolute inset-y-0 right-4 flex items-center text-muted"
                    >
                      ₽
                    </span>
                  </div>
                </Field>
              </div>

              <div className="space-y-2 rounded-3xl bg-subtle/60 p-4 sm:p-5">
                <Checkbox
                  id={fieldId("hasCitizenship")}
                  checked={draft.hasCitizenship}
                  error={showError("hasCitizenship")}
                  onChange={(checked) => {
                    touch("hasCitizenship");
                    setField("hasCitizenship", checked);
                  }}
                >
                  Я гражданин Российской Федерации
                </Checkbox>
                <Checkbox
                  id={fieldId("consentGiven")}
                  checked={draft.consentGiven}
                  error={showError("consentGiven")}
                  onChange={(checked) => {
                    touch("consentGiven");
                    setField("consentGiven", checked);
                  }}
                >
                  Согласен на обработку персональных данных по{" "}
                  <a
                    href="/privacy-policy.pdf"
                    className="font-medium text-brand underline decoration-brand/40 underline-offset-2 hover:decoration-brand"
                    target="_blank"
                    rel="noreferrer noopener"
                  >
                    политике конфиденциальности
                  </a>{" "}
                  (152-ФЗ)
                </Checkbox>
              </div>
            </div>
          </StepCard>
        </form>

        <LoanSummary
          ref={summaryRef}
          product={product}
          amount={amount}
          termMonths={termMonths}
          monthlyIncome={draft.monthlyIncome}
          result={result}
        >
          <div aria-live="polite" data-testid="push-status-region">
            {status === "success" ? (
              <div className="rounded-3xl bg-highlight p-5 text-highlight-on" data-testid="push-status">
                <div className="flex items-center gap-2 font-semibold">
                  <CircleCheck className="h-5 w-5" aria-hidden />
                  {applicationId ? `Заявка №${applicationId} отправлена` : "Заявка отправлена"}
                </div>
                <p className="mt-1.5 text-sm">
                  «{product.title}» на {formatNumber(amount)}&nbsp;₽. Мы свяжемся с вами после рассмотрения.
                </p>
                <button
                  type="button"
                  onClick={startOver}
                  className="mt-4 inline-flex min-h-11 items-center gap-2 rounded-xl bg-highlight-on/10 px-4 text-sm font-semibold transition-colors hover:bg-highlight-on/15 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-highlight-on/20"
                >
                  <RotateCcw className="h-4 w-4" aria-hidden />
                  Новый расчёт
                </button>
              </div>
            ) : (
              <>
                <SubmitButton form={FORM_ID} loading={status === "submitting"} />

                {status === "error" && (
                  <div
                    className="mt-3 flex items-start gap-2 rounded-2xl bg-panel-danger/15 p-3 text-sm text-panel-ink"
                    data-testid="push-status"
                  >
                    <CircleAlert className="mt-0.5 h-4 w-4 shrink-0 text-panel-danger" aria-hidden />
                    <span>
                      {pushError}{" "}
                      <button
                        type="button"
                        onClick={() => handleSubmit()}
                        className="font-semibold underline underline-offset-2"
                      >
                        Повторить
                      </button>
                    </span>
                  </div>
                )}

                {status !== "error" && (
                  <p className="mt-3 text-center text-xs leading-5 text-panel-muted">
                    {submitAttempted && !isValid
                      ? "Проверьте поля, отмеченные красным"
                      : isValid
                        ? "Расчёт предварительный, итоговые условия — после рассмотрения"
                        : "Заполните данные о себе и подтвердите согласия"}
                  </p>
                )}
              </>
            )}
          </div>
        </LoanSummary>
      </div>

      <MobileSummaryBar monthlyPayment={result.monthlyPayment} visible={!summaryInView} />
    </>
  );
}

function clamp(value: number, min: number, max: number): number {
  return Math.min(Math.max(value, min), max);
}
