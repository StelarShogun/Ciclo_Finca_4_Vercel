# Laravel Scheduler en Render (CF4-163)

Este documento describe cómo se ejecuta el **Laravel Scheduler** en producción Render y cómo comprobar que los comandos programados corren correctamente.

## Contexto

Los comandos están definidos en [`routes/console.php`](../routes/console.php). En Render **plan gratis** no hay cron tradicional gratuito ni Pre-deploy Command usable como scheduler persistente. Tampoco se usa `php artisan schedule:work` en este entorno.

La solución adoptada: un **loop en segundo plano** dentro del contenedor web que ejecuta `php artisan schedule:run` cada **60 segundos** mientras el servicio esté activo.

## Producción actual (Docker + Apache)

- **Start Command:** el definido por el `Dockerfile` / entrypoint (`docker-entrypoint.sh`). **No** configurar `bash scripts/render-start.sh` en el dashboard si el servicio ya despliega por Docker.
- Tras el bootstrap (cache, storage link, permisos), el entrypoint arranca el loop y luego `apache2-foreground`.
- El runtime principal sigue siendo **Apache**; el scheduler no reemplaza el servidor web.

Snippet equivalente en [`docker-entrypoint.sh`](../docker-entrypoint.sh):

```bash
(
  while true; do
    php artisan schedule:run --no-interaction >> storage/logs/scheduler.log 2>&1
    sleep 60
  done
) &

echo ">>> Scheduler loop iniciado (schedule:run cada 60s)"
exec apache2-foreground
```

## Limitación importante (Render Free)

Si Render **duerme** el web service por inactividad, el proceso del contenedor se detiene y **el scheduler deja de ejecutarse** hasta que alguien vuelva a despertar el servicio (tráfico HTTP). Esto **no es un fallo del código**; es una restricción del plan gratis. Cuando el servicio despierta, el loop y el heartbeat retoman.

## Comandos programados

| Comando | Horario (app timezone) | Slug en `app_settings` |
|---------|------------------------|-------------------------|
| `scheduler:heartbeat` | Cada minuto | — (solo `scheduler_last_heartbeat_at`) |
| `sales:delete-expired` | Diario 00:00 | `sales_delete_expired` |
| `sales:send-expiry-reminders` | Diario 09:00 | `sales_send_expiry_reminders` |
| `orders:cancel-expired-ready` | Diario 01:00 | `orders_cancel_expired_ready` |
| `reports:send-weekly-dashboard` | Cron desde panel (día/hora/minuto) | `reports_send_weekly_dashboard` |
| `cf4:cleanup-temp-product-images` | Diario 03:30 | `cf4_cleanup_temp_product_images` |

## Poblar claves en producción (antes del primer heartbeat)

Las filas se crean solas cuando corre el scheduler (`updateOrCreate`), pero puedes **pre-crearlas** para verlas en DBeaver desde el deploy:

**Opción A — Artisan (recomendado en Render shell o tras `migrate`):**

```bash
php artisan cf4:seed-scheduler-app-settings
php artisan cf4:seed-scheduler-app-settings --dry-run   # solo listar faltantes
```

**Opción B — Script local con Docker:**

```bash
./scripts/seed-scheduler-app-settings.sh
```

**Opción C — SQL en DBeaver / Aiven:**

Ejecutar [`database/scripts/seed_scheduler_app_settings.sql`](../database/scripts/seed_scheduler_app_settings.sql) (idempotente: no sobrescribe claves existentes).

**Opción D — Migración en deploy:**

`database/migrations/2026_05_23_120000_seed_scheduler_app_settings.php` inserta las mismas claves con `php artisan migrate` (solo si aún no existen).

Todas las opciones insertan **21 claves** (`scheduler_last_heartbeat_at` + 4 campos × 5 comandos) con `value` NULL hasta la primera ejecución.

## Evidencia de ejecución

### 1. Heartbeat (más simple — DBeaver)

Clave en `app_settings`:

- **`scheduler_last_heartbeat_at`** — actualizada por `scheduler:heartbeat` cada minuto mientras el scheduler esté activo.

Consulta de ejemplo:

```sql
SELECT `key`, `value`, `updated_at`
FROM app_settings
WHERE `key` = 'scheduler_last_heartbeat_at';
```

Con el servicio despierto, el valor debería avanzar aproximadamente cada minuto.

### 2. Estado por comando

Para cada slug, existen claves:

- `scheduler_<slug>_last_started_at`
- `scheduler_<slug>_last_success_at`
- `scheduler_<slug>_last_failure_at`
- `scheduler_<slug>_last_status` (`running` | `success` | `failure`)

Ejemplo:

```sql
SELECT `key`, `value`, `updated_at`
FROM app_settings
WHERE `key` LIKE 'scheduler_sales_delete_expired_%'
ORDER BY `key`;
```

### 3. Log en disco

- **`storage/logs/scheduler.log`** — salida de `schedule:run` (loop) y de los comandos programados (vía `appendOutputTo` + canal `scheduler`).

## Qué no hacer

- No exponer endpoints HTTP públicos para ejecutar migraciones o el scheduler.
- No usar `schedule:work` en Render Free con esta arquitectura.
- No cambiar el Start Command a `scripts/render-start.sh` si ya despliegas con Docker + `docker-entrypoint.sh`.

## Script alternativo (sin Docker)

[`scripts/render-start.sh`](../scripts/render-start.sh) documenta un arranque con `php artisan serve` + el mismo loop. Solo para referencia o entornos sin Apache; **no** es el Start Command de producción Docker actual.

## Verificación manual tras deploy

1. Despertar el servicio (request HTTP a la URL pública).
2. Esperar 2–3 minutos.
3. Comprobar `scheduler_last_heartbeat_at` en la base de datos.
4. Revisar `storage/logs/scheduler.log` o logs del servicio en Render.
5. Tras la hora de un comando (o `--force` en staging), comprobar `scheduler_<slug>_last_success_at`.

## Pruebas locales

```bash
php artisan scheduler:heartbeat
composer test -- --filter=SchedulerHeartbeatCommandTest
```
