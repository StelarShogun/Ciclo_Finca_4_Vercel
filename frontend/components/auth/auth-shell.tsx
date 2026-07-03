"use client";

import Link from "next/link";

import { cn } from "@/lib/utils";

/**
 * Piezas visuales de las pantallas de auth, fieles a la vieja
 * clients-users.css (.login-form-box y compañía) adaptadas a Tailwind.
 */

export function AuthBox({ children, className }: { children: React.ReactNode; className?: string }) {
  return (
    <div
      className={cn(
        "flex w-full max-w-[540px] flex-col items-center rounded-2xl bg-card px-6 pb-8 pt-9 text-card-foreground shadow-[0_4px_24px_rgba(0,0,0,0.08)] sm:px-10",
        className,
      )}
    >
      {children}
    </div>
  );
}

export function AuthBackLink({ href = "/", children = "Regresar" }: { href?: string; children?: React.ReactNode }) {
  return (
    <Link
      href={href}
      className="mb-4 inline-flex items-center gap-2 self-start text-sm font-semibold text-[#5f6368] transition-colors hover:text-[#235347] dark:text-muted-foreground dark:hover:text-[#8EB69B]"
    >
      <i className="fas fa-arrow-left" aria-hidden />
      <span>{children}</span>
    </Link>
  );
}

export function AuthLogo() {
  return (
    <div className="mb-3.5">
      {/* eslint-disable-next-line @next/next/no-img-element */}
      <img
        src="/brand/logo-ciclo-finca-icon-64.webp"
        alt="Ciclo Finca 4"
        className="block h-[60px] w-[60px] rounded-full border-2 border-[#235347] bg-card object-cover p-[3px]"
      />
    </div>
  );
}

export function AuthTitle({ children }: { children: React.ReactNode }) {
  return <h2 className="mb-1.5 w-full text-center text-[1.75rem] font-bold">{children}</h2>;
}

export function AuthSubtitle({ children }: { children: React.ReactNode }) {
  return <p className="mb-6 w-full text-center text-[0.93rem] text-muted-foreground">{children}</p>;
}

export function FieldLabel({ htmlFor, icon, children }: { htmlFor: string; icon?: string; children: React.ReactNode }) {
  return (
    <label htmlFor={htmlFor} className="flex items-center gap-2 text-sm font-semibold">
      {icon && <i className={cn(icon, "text-[#235347] dark:text-[#8EB69B]")} aria-hidden />}
      {children}
    </label>
  );
}

export function FieldError({ children }: { children?: React.ReactNode }) {
  if (!children) return null;
  return (
    <p className="mt-1 flex items-center gap-1.5 rounded-md border border-[#f5c6cb] bg-[#fdf0ef] px-2.5 py-1 text-[0.82rem] font-medium text-[#c0392b] dark:border-red-900 dark:bg-red-950/60 dark:text-red-300">
      {children}
    </p>
  );
}

export function AuthSubmitButton({
  children,
  icon,
  disabled,
  pending,
  pendingText,
}: {
  children: React.ReactNode;
  icon?: string;
  disabled?: boolean;
  pending?: boolean;
  pendingText?: string;
}) {
  return (
    <button
      type="submit"
      disabled={disabled || pending}
      className="mt-2 flex w-full items-center justify-center gap-2.5 rounded-[10px] bg-[#235347] px-5 py-3.5 font-bold text-white shadow-[0_4px_14px_rgba(35,83,71,0.22)] transition hover:-translate-y-px hover:bg-[#256428] hover:shadow-[0_6px_20px_rgba(35,83,71,0.32)] active:translate-y-0.5 disabled:cursor-not-allowed disabled:opacity-60 disabled:shadow-none disabled:hover:translate-y-0"
    >
      {icon && <i className={cn(icon, "text-[0.95rem] opacity-90")} aria-hidden />}
      <span>{pending && pendingText ? pendingText : children}</span>
    </button>
  );
}

export function AuthDivider() {
  return (
    <div className="my-5 flex w-full items-center gap-3 text-[0.85rem] text-[#9aa0a6]">
      <span className="h-px flex-1 bg-border" />
      <span>o</span>
      <span className="h-px flex-1 bg-border" />
    </div>
  );
}

const GOOGLE_LETTERS: Array<[string, string]> = [
  ["G", "#4285f4"],
  ["o", "#ea4335"],
  ["o", "#fbbc05"],
  ["g", "#4285f4"],
  ["l", "#34a853"],
  ["e", "#ea4335"],
];

export function GoogleButton({ href }: { href: string }) {
  return (
    <div className="w-full">
      <a
        href={href}
        className="flex w-full items-center justify-center gap-3 rounded-[10px] border-[1.5px] border-[#dadce0] bg-card px-4 py-[11px] text-[0.95rem] font-semibold text-[#3c4043] transition hover:border-[#bdc1c6] hover:bg-muted/60 hover:shadow-sm dark:border-border dark:text-foreground"
      >
        <span
          aria-hidden
          className="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-card font-bold text-[#4285f4] shadow-[0_0_0_1px_#dadce0]"
        >
          G
        </span>
        <span className="inline-flex items-center gap-1.5">
          Continuar con
          <span aria-hidden className="font-bold">
            {GOOGLE_LETTERS.map(([letter, color], i) => (
              <span key={i} style={{ color }}>{letter}</span>
            ))}
          </span>
        </span>
      </a>
      <p className="mt-2.5 text-center text-[0.78rem] leading-relaxed text-muted-foreground">
        Si creas tu cuenta con Google, aceptas los{" "}
        <a href="/legal/terminos" target="_blank" rel="noopener noreferrer" className="font-medium text-[#235347] underline underline-offset-2 dark:text-[#8EB69B]">Términos y condiciones</a>{" "}
        y la{" "}
        <a href="/legal/privacidad" target="_blank" rel="noopener noreferrer" className="font-medium text-[#235347] underline underline-offset-2 dark:text-[#8EB69B]">Política de privacidad</a>.
      </p>
    </div>
  );
}

export function PasswordToggle({ visible, onToggle }: { visible: boolean; onToggle: () => void }) {
  return (
    <button
      type="button"
      onClick={onToggle}
      aria-label="Mostrar/ocultar contraseña"
      className="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground transition-colors hover:text-[#235347] dark:hover:text-[#8EB69B]"
    >
      <i className={`fas ${visible ? "fa-eye-slash" : "fa-eye"}`} aria-hidden />
    </button>
  );
}
