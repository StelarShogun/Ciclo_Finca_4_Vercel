"use client";

import { cn } from "@/lib/utils";

export type CartPaymentMethod = "cash" | "sinpe" | "transfer";

const PAYMENT_OPTIONS: Array<{ value: CartPaymentMethod; label: string; iconClass: string }> = [
  { value: "cash", label: "Efectivo", iconClass: "fas fa-money-bill-wave" },
  { value: "sinpe", label: "SINPE Móvil", iconClass: "fas fa-mobile-screen-button" },
  { value: "transfer", label: "Transferencia", iconClass: "fas fa-building-columns" },
];

type CartSummaryProps = {
  subtotalFormatted: string;
  totalFormatted: string;
  paymentMethod: CartPaymentMethod;
  pickupPolicyLine: string | null;
  isCheckingOut?: boolean;
  onCheckout: () => void;
  onPaymentMethodChange: (method: CartPaymentMethod) => void;
};

/** Resumen del pedido con forma de pago, fiel al CartSummary viejo. */
export function CartSummary({
  subtotalFormatted,
  totalFormatted,
  paymentMethod,
  pickupPolicyLine,
  isCheckingOut = false,
  onCheckout,
  onPaymentMethodChange,
}: CartSummaryProps) {
  return (
    <aside aria-labelledby="cart-summary-title" className="lg:sticky lg:top-20 lg:self-start">
      <div className="rounded-xl border bg-card p-5">
        <h2 id="cart-summary-title" className="mb-4 text-lg font-bold">Total del pedido</h2>

        <fieldset className="mb-4 border-0 p-0">
          <legend className="text-sm font-semibold" id="cart-payment-legend">Forma de pago</legend>
          <p className="mb-2 mt-0.5 text-xs text-muted-foreground">
            Podés cambiarla luego; usamos esto para preparar tu pedido.
          </p>
          <div className="grid grid-cols-3 gap-2" role="radiogroup" aria-labelledby="cart-payment-legend">
            {PAYMENT_OPTIONS.map((option) => (
              <label key={option.value} className="cursor-pointer">
                <input
                  type="radio"
                  name="checkout_payment_method"
                  value={option.value}
                  className="sr-only"
                  checked={paymentMethod === option.value}
                  onChange={() => onPaymentMethodChange(option.value)}
                />
                <span
                  className={cn(
                    "flex flex-col items-center gap-1.5 rounded-lg border-2 px-1.5 py-2.5 text-center text-[11px] font-semibold transition-colors",
                    paymentMethod === option.value
                      ? "border-[#235347] bg-accent text-[#235347] dark:text-[#8EB69B]"
                      : "border-border text-muted-foreground hover:border-[#8EB69B]",
                  )}
                >
                  <i className={cn(option.iconClass, "text-base")} aria-hidden />
                  <span>{option.label}</span>
                </span>
              </label>
            ))}
          </div>
        </fieldset>

        <div className="space-y-2 border-t pt-3 text-sm">
          <div className="flex justify-between">
            <span>Subtotal</span>
            <span>{subtotalFormatted}</span>
          </div>
          <div className="flex justify-between text-muted-foreground">
            <span>Impuestos</span>
            <span>Incluidos / no aplican</span>
          </div>
          <div className="flex justify-between border-t pt-2 text-base font-bold">
            <span>Total estimado</span>
            <span className="text-[#235347] dark:text-[#8EB69B]">{totalFormatted}</span>
          </div>
        </div>

        <div className="mt-4">
          <button
            type="button"
            disabled={isCheckingOut}
            onClick={onCheckout}
            className="flex w-full items-center justify-center gap-2.5 rounded-[10px] bg-[#235347] px-5 py-3 font-bold text-white shadow-[0_4px_14px_rgba(35,83,71,0.22)] transition hover:bg-[#256428] disabled:cursor-not-allowed disabled:opacity-60"
          >
            <i className={isCheckingOut ? "fas fa-spinner fa-spin" : "fas fa-check"} aria-hidden />
            {isCheckingOut ? "Procesando..." : "Confirmar pedido"}
          </button>
          {pickupPolicyLine && (
            <p className="mt-2.5 flex items-start gap-1.5 text-xs text-muted-foreground">
              <i className="fas fa-circle-info mt-0.5" aria-hidden />
              {pickupPolicyLine}
            </p>
          )}
        </div>
      </div>
    </aside>
  );
}
