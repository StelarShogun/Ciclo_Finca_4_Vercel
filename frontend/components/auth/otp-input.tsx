"use client";

import { useRef } from "react";

import { OTP_LENGTH as LENGTH, applyOtpInput } from "@/lib/otp";
import { cn } from "@/lib/utils";

/**
 * OTP de 6 cajas fiel al VerifyCode viejo: auto-avance al escribir,
 * backspace regresa a la anterior y pegar distribuye los dígitos.
 */
export function OtpInput({
  value,
  onChange,
  disabled,
}: {
  value: string;
  onChange: (code: string) => void;
  disabled?: boolean;
}) {
  const inputsRef = useRef<Array<HTMLInputElement | null>>([]);
  const digits = Array.from({ length: LENGTH }, (_, i) => value[i] ?? "");

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
    <fieldset className="mt-1.5 flex w-full justify-center gap-[clamp(6px,2.5vw,12px)] border-0 p-0" aria-label="Código de verificación">
      {digits.map((digit, i) => (
        <input
          key={i}
          ref={(el) => { inputsRef.current[i] = el; }}
          type="text"
          inputMode="numeric"
          autoComplete={i === 0 ? "one-time-code" : "off"}
          aria-label={`Dígito ${i + 1}`}
          disabled={disabled}
          value={digit}
          onChange={(e) => onInput(i, e.target.value)}
          onKeyDown={(e) => onKeyDown(i, e)}
          onFocus={(e) => e.target.select()}
          className={cn(
            "h-[clamp(50px,15vw,64px)] w-[clamp(42px,13vw,56px)] rounded-xl border-2 border-border bg-background text-center text-[clamp(1.4rem,6vw,1.9rem)] font-bold caret-[#12B36A] outline-none transition",
            "hover:border-[#12B36A] focus:-translate-y-0.5 focus:border-[#12B36A] focus:shadow-[0_0_0_4px_rgba(18,179,106,0.15)]",
            digit && "border-[#12B36A]/60",
          )}
        />
      ))}
    </fieldset>
  );
}
