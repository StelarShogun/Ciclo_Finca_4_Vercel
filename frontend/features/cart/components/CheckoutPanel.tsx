"use client";

import { CartSummary, type CartPaymentMethod } from "@/components/storefront/cart/cart-summary";

type CheckoutPanelProps = {
  subtotalFormatted: string;
  totalFormatted: string;
  paymentMethod: CartPaymentMethod;
  pickupPolicyLine: string | null;
  isCheckingOut: boolean;
  onCheckout: () => void;
  onPaymentMethodChange: (method: CartPaymentMethod) => void;
};

export function CheckoutPanel(props: CheckoutPanelProps) {
  return <CartSummary {...props} />;
}
