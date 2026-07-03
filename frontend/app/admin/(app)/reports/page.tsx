"use client";

import Link from "next/link";
import {
  ArrowRight,
  BarChart3,
  ClipboardList,
  FileDown,
  History,
  LineChart,
  PieChart,
  Search,
  Users,
} from "lucide-react";

import { PageHeader } from "@/components/admin/page-header";
import { Card, CardContent } from "@/components/ui/card";

const REPORTS = [
  { icon: FileDown, title: "Exportar datos", desc: "Descargas centralizadas: PDF, Excel y XML de inventario y ventas, más proveedores, marcas, pedidos a proveedores, usuarios y encargos.", href: "/admin/reports/exports" },
  { icon: LineChart, title: "Desempeño de ventas", desc: "Filtra por día, semana, mes, año o un rango personalizado. Consulta totales e ingresos y la variación frente al periodo anterior.", href: "/admin/reports/sales-performance" },
  { icon: Users, title: "Compras por cliente", desc: "Historial de ventas completadas por usuario: totales, cantidad de órdenes y ticket promedio; detalle por periodo.", href: "/admin/reports/client-purchases" },
  { icon: BarChart3, title: "Productos más vendidos", desc: "Consulta cuánto se vendió de cada producto y cuánto ingresó. Busca por nombre o código y descubre cuáles son los favoritos.", href: "/admin/reports/product-sales" },
  { icon: Search, title: "Productos más buscados", desc: "Ranking según apariciones en el catálogo cuando los clientes buscan por texto (últimos 7, 30 o 90 días).", href: "/admin/reports/catalog-search" },
  { icon: History, title: "Movimientos de inventario", desc: "Consulta el historial completo de entradas, salidas y devoluciones por producto. Filtra por tipo, origen y rango de fechas.", href: "/admin/reports/movements" },
  { icon: ClipboardList, title: "Bitácora de auditoría", desc: "Consulta acciones administrativas por usuario, tipo de evento, módulo afectado y fecha para detectar irregularidades.", href: "/admin/audit" },
  { icon: PieChart, title: "Ventas por categoría", desc: "Analiza el rendimiento de ventas agrupado por categoría de producto. Identifica cuáles categorías generan más ingresos.", href: "/admin/reports/category-sales" },
];

export default function ReportsHubPage() {
  return (
    <>
      <PageHeader
        kicker="Reportes"
        icon="fa-file-lines"
        title="Centro de reportes"
        description="Consulta reportes analíticos de ventas, inventario, clientes, búsquedas y actividad administrativa."
      />

      <div className="grid gap-4 sm:grid-cols-2">
        {REPORTS.map((r) => (
          <Link key={r.href} href={r.href}>
            <Card className="h-full transition-shadow hover:shadow-md">
              <CardContent className="flex items-start gap-4 p-5">
                <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-accent text-brand-medium dark:text-brand-light">
                  <r.icon className="h-5 w-5" />
                </span>
                <div className="min-w-0 flex-1">
                  <p className="font-semibold">{r.title}</p>
                  <p className="mt-0.5 text-sm text-muted-foreground">{r.desc}</p>
                </div>
                <ArrowRight className="mt-1 h-5 w-5 shrink-0 text-muted-foreground" />
              </CardContent>
            </Card>
          </Link>
        ))}
      </div>
    </>
  );
}
