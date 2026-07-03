import type { ReactNode } from "react";

type PageHeaderProps = {
  title: string;
  description?: ReactNode;
  /** Etiqueta corta en versalitas sobre el título (ej. "Productos"). */
  kicker?: string;
  /** Clase FontAwesome del medallón (ej. "fa-box"). */
  icon?: string;
  actions?: ReactNode;
  children?: ReactNode;
};

/**
 * Encabezado de módulo: tarjeta con degradado de marca + medallón de ícono,
 * kicker, título y descripción. Réplica del `.page-header` del admin viejo,
 * usado en todos los módulos (también los "mini-hubs" de cada pantalla).
 */
export function PageHeader({ title, description, kicker, icon, actions, children }: PageHeaderProps) {
  return (
    <header
      className="relative mb-6 flex flex-wrap items-center justify-between gap-5 overflow-hidden rounded-2xl border border-white/10 px-6 py-6 text-white shadow-lg"
      style={{
        background:
          "radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--brand-light) 22%, transparent), transparent 60%), linear-gradient(135deg, var(--brand-dark) 0%, var(--brand-darkest) 100%)",
      }}
    >
      {/* Retícula de puntos + brillo de acento que llena el lado derecho. */}
      <span
        aria-hidden
        className="pointer-events-none absolute inset-0 z-0"
        style={{
          backgroundImage:
            "radial-gradient(circle at 90% -20%, color-mix(in srgb, var(--cta) 38%, transparent), transparent 46%), radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1.4px)",
          backgroundSize: "auto, 18px 18px",
          maskImage: "linear-gradient(90deg, transparent 38%, #000 100%)",
          WebkitMaskImage: "linear-gradient(90deg, transparent 38%, #000 100%)",
        }}
      />
      {/* Línea de acento inferior. */}
      <span
        aria-hidden
        className="pointer-events-none absolute bottom-0 left-6 right-6 h-0.5 rounded-full opacity-60"
        style={{ background: "linear-gradient(90deg, var(--cta), transparent 70%)" }}
      />

      <div className="relative z-10 flex min-w-0 items-center gap-4">
        {icon ? (
          <span
            aria-hidden
            className="relative flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 text-2xl text-white shadow-lg"
            style={{
              background:
                "linear-gradient(135deg, color-mix(in srgb, var(--cta) 42%, transparent), rgba(255,255,255,0.04))",
            }}
          >
            <i className={`fas ${icon}`} />
          </span>
        ) : null}
        <div className="min-w-0">
          {kicker ? (
            <p className="mb-1 text-xs font-bold uppercase tracking-[0.16em] text-[var(--brand-light)]">
              {kicker}
            </p>
          ) : null}
          <h1 className="text-2xl font-bold leading-tight tracking-tight">{title}</h1>
          {description ? (
            <p className="mt-1.5 max-w-[70ch] text-sm leading-relaxed text-white/85">{description}</p>
          ) : null}
          {children}
        </div>
      </div>

      {actions ? (
        <div className="relative z-10 flex flex-shrink-0 flex-wrap items-center gap-2.5">{actions}</div>
      ) : null}
    </header>
  );
}
