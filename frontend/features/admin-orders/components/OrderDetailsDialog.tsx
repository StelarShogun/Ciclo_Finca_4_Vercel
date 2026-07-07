"use client";

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import type { SaleDetail } from "@/lib/api/admin/sales";
import { formatCRC } from "@/lib/money";

export function OrderDetailsDialog({
  detail,
  onClose,
}: {
  detail: SaleDetail | null;
  onClose: () => void;
}) {
  return (
    <Dialog open={!!detail} onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-[56rem]">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <i className="fas fa-clipboard-list text-brand-medium dark:text-brand-light" aria-hidden />
            Encargo {detail?.invoice_number ?? ""}
          </DialogTitle>
          <DialogDescription>{detail?.sale_date_label}</DialogDescription>
        </DialogHeader>
        {detail && (
          <div className="space-y-3 text-sm">
            <p className="font-medium">
              {detail.client ? `${detail.client.name} ${detail.client.first_surname}` : detail.buyer.name ?? "Mostrador"}
            </p>
            <div className="divide-y rounded-md border">
              {detail.sale_items.map((item) => (
                <div key={item.id} className="flex justify-between px-3 py-2">
                  <span>
                    {item.product?.name ?? `#${item.product_id}`} × {item.quantity}
                  </span>
                  <span>{formatCRC(item.total)}</span>
                </div>
              ))}
            </div>
            <div className="flex justify-between font-semibold">
              <span>Total</span>
              <span>{formatCRC(detail.total)}</span>
            </div>
          </div>
        )}
      </DialogContent>
    </Dialog>
  );
}
