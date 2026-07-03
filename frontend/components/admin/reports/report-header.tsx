import Link from "next/link";
import { ArrowLeft } from "lucide-react";

import { PageHeader } from "@/components/admin/page-header";
import { Button } from "@/components/ui/button";

export function ReportHeader({
  title,
  description,
  icon,
  actions,
}: {
  title: string;
  description?: React.ReactNode;
  /** Clase FontAwesome del medallón (ej. "fa-chart-line"). */
  icon?: string;
  actions?: React.ReactNode;
}) {
  return (
    <PageHeader
      kicker="Reportes"
      icon={icon}
      title={title}
      description={description}
      actions={
        <>
          {actions}
          <Button
            asChild
            variant="outline"
            className="border-white/25 bg-white/10 text-white hover:bg-white/20 hover:text-white"
          >
            <Link href="/admin/reports">
              <ArrowLeft className="h-4 w-4" /> Volver
            </Link>
          </Button>
        </>
      }
    />
  );
}
