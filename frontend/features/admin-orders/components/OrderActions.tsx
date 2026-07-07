"use client";

import { ActionBar, ActionBtn } from "@/components/admin/action-btn";
import { invoiceUrl } from "@/lib/api/admin/sales";
import type { OrderRow } from "@/lib/api/admin/orders";
import { orderActionsForStatus } from "../order-actions";

type OrderActionsProps = {
  order: OrderRow;
  onView: (id: number) => void;
  onMarkReady: (id: number) => void;
  onComplete: (id: number) => void;
  onCancel: (id: number) => void;
};

export function OrderActions({ order, onView, onMarkReady, onComplete, onCancel }: OrderActionsProps) {
  const actions = orderActionsForStatus(order.status);

  return (
    <ActionBar>
      {actions.includes("view") && (
        <ActionBtn icon="fa-eye" label="Ver detalle" tone="view" onClick={() => onView(order.sale_id)} />
      )}
      {actions.includes("mark-ready") && (
        <ActionBtn icon="fa-box-open" label="Marcar listo" tone="stock" onClick={() => onMarkReady(order.sale_id)} />
      )}
      {actions.includes("complete") && (
        <ActionBtn icon="fa-circle-check" label="Confirmar" tone="activate" onClick={() => onComplete(order.sale_id)} />
      )}
      {actions.includes("cancel") && (
        <ActionBtn icon="fa-xmark" label="Rechazar" tone="delete" onClick={() => onCancel(order.sale_id)} />
      )}
      {actions.includes("invoice") && (
        <a
          href={invoiceUrl(order.sale_id)}
          target="_blank"
          rel="noopener noreferrer"
          title="Ver factura"
          aria-label="Ver factura"
          className="inline-flex h-8 w-8 items-center justify-center rounded-md text-sky-600 transition-colors hover:bg-sky-100 dark:text-sky-400 dark:hover:bg-sky-950"
        >
          <i className="fas fa-file-invoice" aria-hidden />
        </a>
      )}
    </ActionBar>
  );
}
