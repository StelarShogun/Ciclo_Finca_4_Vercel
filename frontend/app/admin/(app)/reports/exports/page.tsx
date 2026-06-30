"use client";

import { Download } from "lucide-react";

import { REPORT_EXPORTS } from "@/lib/api/admin/reports";
import { ReportHeader } from "@/components/admin/reports/report-header";
import { Button } from "@/components/ui/button";
import { Card, CardContent } from "@/components/ui/card";

export default function ExportsReport() {
  return (
    <>
      <ReportHeader title="Exportar datos" description="Descargas de reportes en PDF y Excel (servidas por el backend)." />
      <div className="grid gap-3 sm:grid-cols-2">
        {REPORT_EXPORTS.map((e) => (
          <Card key={e.label}>
            <CardContent className="flex items-center justify-between gap-3 p-4">
              <div>
                <p className="font-medium">{e.label}</p>
                <p className="text-xs text-muted-foreground">{e.format}</p>
              </div>
              <Button asChild size="sm" variant="outline">
                <a href={e.href} target="_blank" rel="noopener noreferrer"><Download className="h-4 w-4" /> Descargar</a>
              </Button>
            </CardContent>
          </Card>
        ))}
      </div>
    </>
  );
}
