# Evidencias — Seguimiento 8

Carpeta para capturas, logs y reportes del entregable DevOps (rúbrica EIF-406).

## Estructura

```
docs/evidencia/
├── README.md
├── aaron/          # Plantilla / capturas estáticas del integrante
├── arturo/
├── darwin/
├── dilan/
└── YYYY-MM-DD/     # Generada automáticamente por el script
    ├── RESUMEN.txt
    ├── aaron/
    │   ├── 01-phpunit-api.log
    │   ├── 02-newman-api.log
    │   └── 03-dusk-admin-login.log
    ├── arturo/
    ├── darwin/
    ├── dilan/
    └── pipeline/
        ├── 01-ci-parity.log      # solo con FULL=1
        └── 02-production-up.log
```

## Ejecución automática (sin intervención manual)

Desde la raíz del repo, con Docker levantado:

```bash
./scripts/run-seguimiento-8-evidence.sh
```

Incluye paridad CI completa:

```bash
FULL=1 ./scripts/run-seguimiento-8-evidence.sh
```

Equivalente npm:

```bash
npm run test:seguimiento8:evidence
```

## Qué agregar manualmente al documento del curso

- Capturas de **GitHub Actions** (jobs verde y rojo).
- Capturas de **Render** (deploy / sin deploy).
- Screenshot del dashboard **Pulse** (`/pulse` como admin) — complementa el test automatizado de Dilan.
- Enlace al **video grupal** (commit → CI → deploy → cambio visible).

## Referencias

- [PRUEBAS_EQUIPO_SEGUIMIENTO_8.md](../PRUEBAS_EQUIPO_SEGUIMIENTO_8.md) — matriz y guiones por integrante.
- [SEGUIMIENTO_8.md](../SEGUIMIENTO_8.md) — guía técnica completa.
