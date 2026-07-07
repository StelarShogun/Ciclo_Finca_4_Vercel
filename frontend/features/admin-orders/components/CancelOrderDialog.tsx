"use client";

import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

type CancelOrderDialogProps = {
  open: boolean;
  reason: string;
  isPending: boolean;
  onReasonChange: (value: string) => void;
  onClose: () => void;
  onSubmit: () => void;
};

export function CancelOrderDialog({
  open,
  reason,
  isPending,
  onReasonChange,
  onClose,
  onSubmit,
}: CancelOrderDialogProps) {
  const valid = reason.trim().length >= 3;

  return (
    <Dialog open={open} onOpenChange={(nextOpen) => !nextOpen && onClose()}>
      <DialogContent className="sm:max-w-[38rem]">
        <form
          onSubmit={(event) => {
            event.preventDefault();
            if (valid) onSubmit();
          }}
        >
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <i className="fas fa-triangle-exclamation text-[#d32f2f] dark:text-[#F87171]" aria-hidden />
              Rechazar encargo
            </DialogTitle>
            <DialogDescription>Libera el stock reservado. Indicá el motivo.</DialogDescription>
          </DialogHeader>
          <div className="space-y-1.5 py-4">
            <Label htmlFor="reason">Motivo</Label>
            <Textarea
              id="reason"
              autoFocus
              minLength={3}
              maxLength={500}
              value={reason}
              onChange={(event) => onReasonChange(event.target.value)}
              placeholder="Mínimo 3 caracteres…"
            />
          </div>
          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose}>
              Cancelar
            </Button>
            <Button type="submit" variant="destructive" disabled={!valid || isPending}>
              Rechazar
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
