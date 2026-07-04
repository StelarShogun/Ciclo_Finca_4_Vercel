"use client";

import { useEffect, useRef, useState } from "react";

import { OTP_LENGTH as LENGTH, applyOtpInput } from "@/lib/otp";
import { cn } from "@/lib/utils";

export type OtpStatus = "idle" | "verifying" | "success" | "fail";

/**
 * OTP de 6 cajas fiel al diseño viejo (verify_gmail_code): auto-avance,
 * backspace regresa, pegar distribuye, pop por dígito, shake en error,
 * onda al verificar y colapso en insignia ✓/✕ según el resultado.
 * `errorSignal` es un contador: cada incremento dispara el shake.
 */
export function OtpInput({
  value,
  onChange,
  status = "idle",
  errorSignal = 0,
  disabled,
}: {
  value: string;
  onChange: (code: string) => void;
  status?: OtpStatus;
  errorSignal?: number;
  disabled?: boolean;
}) {
  const inputsRef = useRef<Array<HTMLInputElement | null>>([]);
  const [shaking, setShaking] = useState(false);
  const digits = Array.from({ length: LENGTH }, (_, i) => value[i] ?? "");
  const locked = disabled || status !== "idle";

  useEffect(() => {
    if (!errorSignal) return;
    const frame = window.requestAnimationFrame(() => setShaking(true));
    inputsRef.current[0]?.focus();
    const t = setTimeout(() => setShaking(false), 450);
    return () => {
      window.cancelAnimationFrame(frame);
      clearTimeout(t);
    };
  }, [errorSignal]);

  function commit(next: string[], focusIndex?: number) {
    onChange(next.join(""));
    if (focusIndex != null) inputsRef.current[Math.max(0, Math.min(LENGTH - 1, focusIndex))]?.focus();
  }

  function onInput(index: number, raw: string) {
    const result = applyOtpInput(digits, index, raw);
    commit(result.digits, result.focus ?? undefined);
  }

  function onKeyDown(index: number, e: React.KeyboardEvent<HTMLInputElement>) {
    if (e.key === "Backspace" && !digits[index] && index > 0) {
      e.preventDefault();
      const next = [...digits];
      next[index - 1] = "";
      commit(next, index - 1);
    }
    if (e.key === "ArrowLeft") inputsRef.current[index - 1]?.focus();
    if (e.key === "ArrowRight") inputsRef.current[index + 1]?.focus();
  }

  return (
    <fieldset
      className={cn(
        "otp-inputs mt-1.5 flex w-full justify-center gap-2 border-0 p-0 sm:gap-3",
        shaking && "is-error",
        status === "verifying" && "is-verifying",
        status === "success" && "is-success",
        status === "fail" && "is-fail",
      )}
      aria-label="Código de verificación"
    >
      {digits.map((digit, i) => (
        <input
          key={i}
          ref={(el) => { inputsRef.current[i] = el; }}
          type="text"
          inputMode="numeric"
          autoComplete={i === 0 ? "one-time-code" : "off"}
          aria-label={`Dígito ${i + 1}`}
          disabled={disabled}
          readOnly={locked && !disabled}
          value={digit}
          onChange={(e) => onInput(i, e.target.value)}
          onKeyDown={(e) => onKeyDown(i, e)}
          onFocus={(e) => e.target.select()}
          className={cn(
            "otp-box aspect-[7/8] min-w-0 flex-1 rounded-xl border-2 border-border bg-background text-center text-[clamp(1.3rem,5vw,1.9rem)] font-bold caret-cta outline-none transition",
            "max-w-[56px] hover:border-cta focus:-translate-y-0.5 focus:border-cta focus:shadow-[0_0_0_4px_rgba(18,179,106,0.15)]",
            digit && "is-filled",
          )}
        />
      ))}
      <span className="otp-result" aria-hidden>
        {status === "fail" ? "✕" : "✓"}
      </span>
    </fieldset>
  );
}
