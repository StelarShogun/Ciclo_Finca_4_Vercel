"use client";

import Link from "next/link";
import { CheckCircle2 } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";
import type { CheckoutResult } from "@/lib/api/client/cart";

export function CheckoutSuccess({ result }: { result: CheckoutResult }) {
  return (
    <div className="mx-auto max-w-md px-4 py-16">
      <Card className="border-t-4 border-t-brand-medium">
        <CardContent className="flex flex-col items-center gap-4 py-12 text-center">
          <CheckCircle2 className="h-12 w-12 text-brand-medium" />
          <h1 className="text-xl font-semibold">¡Pedido confirmado!</h1>
          <p className="text-sm text-muted-foreground">
            Factura <span className="font-medium text-foreground">{result.invoice_number}</span>. Te avisaremos cuando
            esté listo para retirar.
          </p>
          <div className="flex gap-2">
            <Button asChild variant="outline">
              <Link href="/catalog">Seguir comprando</Link>
            </Button>
            <Button asChild className="bg-brand-medium hover:bg-brand-medium-dark">
              <Link href="/invoices">Mis facturas</Link>
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
