"use client";

import { useEffect, useState } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { keepPreviousData, useQuery } from "@tanstack/react-query";
import { isAxiosError } from "axios";

import { getInvoices } from "@/lib/api/client/account";
import { useMe } from "@/lib/auth/use-me";
import { StatusBadge, type StatusTone } from "@/components/admin/status-badge";
import { Card, CardContent } from "@/components/ui/card";
import { Skeleton } from "@/components/ui/skeleton";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { Tabs, TabsList, TabsTrigger } from "@/components/ui/tabs";

function tone(t: string): StatusTone {
  if (t === "completed") return "success";
  if (t === "ready" || t === "pending") return "warning";
  if (t === "cancelled") return "danger";
  return "neutral";
}

export default function InvoicesPage() {
  const router = useRouter();
  const me = useMe();
  const [tab, setTab] = useState("facturas");

  useEffect(() => {
    const unauth = me.isError && isAxiosError(me.error) && me.error.response?.status === 401;
    if (unauth || (me.data && me.data.type !== "client")) {
      router.replace("/login?redirect=/invoices");
    }
  }, [me.isError, me.error, me.data, router]);

  const { data, isLoading } = useQuery({
    queryKey: ["invoices", tab],
    queryFn: () => getInvoices(tab),
    placeholderData: keepPreviousData,
  });

  return (
    <div className="mx-auto max-w-4xl px-4 py-12">
      <h1 className="mb-6 text-2xl font-semibold tracking-tight">Mis facturas</h1>

      <Tabs value={tab} onValueChange={setTab} className="mb-4">
        <TabsList>
          <TabsTrigger value="facturas">Activas</TabsTrigger>
          <TabsTrigger value="historial">Historial</TabsTrigger>
          <TabsTrigger value="canceladas">Canceladas</TabsTrigger>
        </TabsList>
      </Tabs>

      {isLoading || !data ? (
        <Skeleton className="h-64" />
      ) : data.orders.length === 0 ? (
        <Card><CardContent className="py-12 text-center text-sm text-muted-foreground">No hay facturas en esta sección.</CardContent></Card>
      ) : (
        <Card>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead>Factura</TableHead>
                  <TableHead>Fecha</TableHead>
                  <TableHead>Estado</TableHead>
                  <TableHead className="text-right">Total</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {data.orders.map((o) => (
                  <TableRow key={o.id} className="cursor-pointer" onClick={() => router.push(`/invoices/${o.id}`)}>
                    <TableCell>
                      <Link href={`/invoices/${o.id}`} className="font-medium hover:underline">
                        {o.invoiceNumber ?? `#${o.id}`}
                      </Link>
                    </TableCell>
                    <TableCell className="text-sm text-muted-foreground">{o.saleDateLabel}</TableCell>
                    <TableCell><StatusBadge tone={tone(o.statusTone)}>{o.statusLabel}</StatusBadge></TableCell>
                    <TableCell className="text-right font-medium">{o.totalFormatted}</TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      )}
    </div>
  );
}
