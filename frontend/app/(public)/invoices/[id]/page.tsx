"use client";

import Link from "next/link";
import { useParams } from "next/navigation";
import { useQuery } from "@tanstack/react-query";
import { ArrowLeft, Printer } from "lucide-react";

import { getInvoice } from "@/lib/api/client/account";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "";

export default function InvoiceDetailPage() {
  const { id } = useParams<{ id: string }>();
  const { data, isLoading, isError } = useQuery({
    queryKey: ["invoice", id],
    queryFn: () => getInvoice(id),
    enabled: !!id,
  });

  if (isLoading) return <div className="mx-auto max-w-3xl px-4 py-12"><Skeleton className="h-96" /></div>;
  if (isError || !data) {
    return (
      <div className="mx-auto max-w-3xl px-4 py-16">
        <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No fue posible cargar la factura.</CardContent></Card>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <div className="mb-4 flex items-center justify-between">
        <Button asChild variant="ghost" size="sm">
          <Link href="/invoices"><ArrowLeft className="h-4 w-4" /> Volver</Link>
        </Button>
        <Button asChild variant="outline" size="sm">
          <a href={`${API_URL}${data.printUrl}`} target="_blank" rel="noopener noreferrer">
            <Printer className="h-4 w-4" /> Imprimir
          </a>
        </Button>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>{data.invoiceNumber ?? "Factura"}</CardTitle>
          <div className="flex flex-wrap gap-x-6 gap-y-1 text-sm text-muted-foreground">
            <span>{data.orderMeta.saleDateLabel}</span>
            <span>{data.orderMeta.statusLabel}</span>
            <span>{data.orderMeta.paymentDisplay}</span>
          </div>
        </CardHeader>
        <CardContent className="space-y-6">
          {data.orderMeta.cancellationReason && (
            <p className="rounded-md bg-red-50 p-3 text-sm text-red-800">
              Motivo de cancelación: {data.orderMeta.cancellationReason}
            </p>
          )}

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Producto</TableHead>
                <TableHead className="text-right">Cant.</TableHead>
                <TableHead className="text-right">Precio</TableHead>
                <TableHead className="text-right">Total</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.items.map((it) => (
                <TableRow key={it.productId}>
                  <TableCell>{it.name}</TableCell>
                  <TableCell className="text-right">{it.quantity}</TableCell>
                  <TableCell className="text-right">{it.unitPriceFormatted}</TableCell>
                  <TableCell className="text-right">{it.totalFormatted}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>

          <div className="ml-auto w-60 space-y-1 text-sm">
            <div className="flex justify-between text-muted-foreground"><span>Subtotal</span><span>{data.totals.subtotalFormatted}</span></div>
            <div className="flex justify-between text-muted-foreground"><span>IVA</span><span>{data.totals.ivaFormatted}</span></div>
            <div className="flex justify-between text-muted-foreground"><span>Descuento</span><span>{data.totals.discountFormatted}</span></div>
            <div className="flex justify-between border-t pt-1 text-base font-semibold"><span>Total</span><span className="text-[#235347]">{data.totals.totalFormatted}</span></div>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
