import { useCallback, useState } from "react";
import type { LoanApplication } from "@/entities/applicant";

export type PushStatus = "idle" | "submitting" | "success" | "error";

interface UseLoanCalculatorPushResult {
  push: (application: LoanApplication) => Promise<boolean>;
  status: PushStatus;
  error: string | undefined;
  /** Номер заявки, который вернул сервер */
  applicationId: number | undefined;
  reset: () => void;
}

/** Отправка заявки на сервер. true — сервер принял заявку. */
export function useLoanCalculatorPush(): UseLoanCalculatorPushResult {
  const [status, setStatus] = useState<PushStatus>("idle");
  const [error, setError] = useState<string | undefined>();
  const [applicationId, setApplicationId] = useState<number | undefined>();

  const push = useCallback(async (application: LoanApplication) => {
    setStatus("submitting");
    setError(undefined);
    try {
      const res = await fetch("/api/applications", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify(application),
      });
      const body = await res.json().catch(() => null);
      if (!res.ok) {
        const first = body?.issues?.[0]?.message as string | undefined;
        throw new Error(first || `Сервер вернул ошибку (${res.status})`);
      }
      setApplicationId(typeof body?.id === "number" ? body.id : undefined);
      setStatus("success");
      return true;
    } catch (err) {
      setError(
        err instanceof TypeError
          ? "Не удалось связаться с сервером. Проверьте соединение."
          : err instanceof Error
            ? err.message
            : "Неизвестная ошибка",
      );
      setStatus("error");
      return false;
    }
  }, []);

  const reset = useCallback(() => {
    setStatus("idle");
    setError(undefined);
    setApplicationId(undefined);
  }, []);

  return { push, status, error, applicationId, reset };
}
