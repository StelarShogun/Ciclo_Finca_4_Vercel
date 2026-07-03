import Link from "next/link";

export function LegalShell({
  title,
  updated,
  children,
}: {
  title: string;
  updated?: string;
  children: React.ReactNode;
}) {
  return (
    <div className="mx-auto max-w-3xl px-4 py-12">
      <nav className="mb-4 text-sm text-muted-foreground">
        <Link href="/" className="hover:underline">Inicio</Link> / <span className="text-foreground">{title}</span>
      </nav>
      <h1 className="text-2xl font-semibold tracking-tight">{title}</h1>
      {updated && <p className="mt-1 text-sm text-muted-foreground">Última actualización: {updated}</p>}
      <div className="prose prose-sm mt-6 max-w-none space-y-5 text-foreground/85 [&_h2]:mt-6 [&_h2]:text-base [&_h2]:font-semibold [&_h2]:text-foreground [&_ul]:list-disc [&_ul]:pl-5 [&_li]:mt-1">
        {children}
      </div>
    </div>
  );
}
