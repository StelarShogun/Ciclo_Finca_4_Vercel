"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useMutation, useQuery, useQueryClient } from "@tanstack/react-query";
import { isAxiosError } from "axios";
import { toast } from "sonner";
import { CheckCircle2 } from "lucide-react";

import { checkout, getCart, type CheckoutResult } from "@/lib/api/client/cart";
import { useMe } from "@/lib/auth/use-me";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

function errMsg(e: unknown, fallback: string) {
  return (isAxiosError(e) && (e.response?.data?.message as string)) || fallback;
}

export default function CheckoutPage() {
  const router = useRouter();
  const queryClient = useQueryClient();
  const me = useMe();
  const [payment, setPayment] = useState("cash");
  const [done, setDone] = useState<CheckoutResult | null>(null);

  // Guard: requiere cliente.
  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/checkout");
    }
  }, [me.isError, me.error, me.data, router]);

  const cart = useQuery({ queryKey: ["cart"], queryFn: getCart });

  const place = useMutation({
    mutationFn: () => checkout(payment),
    onSuccess: (res) => {
      setDone(res);
      queryClient.invalidateQueries({ queryKey: ["cart"] });
    },
    onError: (e) => toast.error(errMsg(e, "No fue posible procesar el pedido.")),
  });

  if (done) {
    return (
      <div className="mx-auto max-w-md px-4 py-16">
        <Card className="border-t-4 border-t-brand-medium">
          <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
            <CheckCircle2 className="h-12 w-12 text-brand-medium" />
            <h1 className="text-xl font-semibold">¡Pedido confirmado!</h1>
            <p className="text-sm text-muted-foreground">
              Factura <span className="font-medium text-foreground">{done.invoice_number}</span>. Te avisaremos cuando esté listo para retirar.
            </p>
            <div className="flex gap-2">
              <Button asChild variant="outline"><Link href="/catalog">Seguir comprando</Link></Button>
              <Button asChild className="bg-brand-medium hover:bg-brand-medium-dark"><Link href="/invoices">Mis facturas</Link></Button>
            </div>
          </CardContent>
        </Card>
      </div>
    );
  }

  if (me.isLoading || cart.isLoading) {
    return <div className="mx-auto max-w-3xl px-4 py-12"><Skeleton className="h-64" /></div>;
  }

  const items = cart.data?.items ?? [];

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <h1 className="mb-6 text-2xl font-semibold tracking-tight">Finalizar compra</h1>

      {items.length === 0 ? (
        <Card>
          <CardContent className="py-12 text-center text-sm text-muted-foreground">
            Tu carrito está vacío.{" "}
            <Link href="/catalog" className="text-brand-medium hover:underline">Ver catálogo</Link>
          </CardContent>
        </Card>
      ) : (
        <div className="grid gap-6 lg:grid-cols-[1fr_320px]">
          <Card>
            <CardHeader><CardTitle>Resumen del pedido</CardTitle></CardHeader>
            <CardContent className="divide-y">
              {items.map((i) => (
                <div key={i.productId} className="flex justify-between py-2 text-sm">
                  <span>{i.name} × {i.quantity}</span>
                  <span className="font-medium">{i.subtotalFormatted}</span>
                </div>
              ))}
            </CardContent>
          </Card>

          <Card className="h-fit">
            <CardHeader><CardTitle>Pago y retiro</CardTitle></CardHeader>
            <CardContent className="space-y-4">
              <div className="space-y-1.5">
                <Label>Método de pago</Label>
                <Select value={payment} onValueChange={setPayment}>
                  <SelectTrigger><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="cash">Efectivo (al retirar)</SelectItem>
                    <SelectItem value="sinpe">SINPE Móvil</SelectItem>
                    <SelectItem value="transfer">Transferencia</SelectItem>
                  </SelectContent>
                </Select>
              </div>
              {cart.data?.pickupPolicyNotice && (
                <p className="text-xs text-muted-foreground">{cart.data.pickupPolicyNotice}</p>
              )}
              <div className="flex justify-between border-t pt-3 text-base font-semibold">
                <span>Total</span>
                <span className="text-brand-medium">{cart.data?.totalFormatted}</span>
              </div>
              <Button
                className="w-full bg-brand-medium hover:bg-brand-medium-dark"
                disabled={place.isPending}
                onClick={() => place.mutate()}
              >
                {place.isPending ? "Procesando…" : "Confirmar pedido"}
              </Button>
            </CardContent>
          </Card>
        </div>
      )}
    </div>
  );
}
