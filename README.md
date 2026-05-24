<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Tests locales (MySQL en Docker)

Los tests Feature usan MySQL real (mismas migraciones que producción). Pasos:

1. Levanta los contenedores:

   ```bash
   docker compose up -d db_ciclo
   ```

2. Crea la base `laravel_test` (una sola vez):

   ```bash
   docker exec mysql_db_ciclo mysql -uroot -proot \
       -e "CREATE DATABASE IF NOT EXISTS laravel_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
           GRANT ALL PRIVILEGES ON laravel_test.* TO 'ciclo_finca_4'@'%';
           FLUSH PRIVILEGES;"
   ```

3. Copia `.env.testing.example` a `.env.testing` y deja `DB_HOST=127.0.0.1` y `DB_PORT=3307` (puerto que expone `docker-compose.yml`).

4. Ejecuta la suite con:

   ```bash
   composer test
   # o, equivalentemente
   ./scripts/test --filter=CF4ClientCartTest
   ```

### Habilitar `pdo_mysql` / `mysqli` sin tocar `/etc/php/php.ini`

En distribuciones tipo Arch / CachyOS las extensiones `pdo_mysql` y `mysqli` vienen instaladas pero **deshabilitadas** en `/etc/php/php.ini`. Para evitar pedir privilegios de root:

- Este repo trae `scripts/php-ini.d/99-mysql.ini` que activa ambas extensiones.
- `composer test` y `./scripts/test` exportan `PHP_INI_SCAN_DIR=:scripts/php-ini.d`, de modo que PHP carga ese `.ini` adicional sin perder los del sistema.
- Si prefieres habilitarlas globalmente, descomenta `extension=pdo_mysql` y `extension=mysqli` en `/etc/php/php.ini` (requiere sudo) y ya no necesitas `PHP_INI_SCAN_DIR`.

> **Comprobación rápida**: `PHP_INI_SCAN_DIR=:scripts/php-ini.d php -m | grep -E 'pdo_mysql|mysqli'` debe imprimir ambos.

## Dependencias y assets en Docker (onboarding)

Tras clonar y configurar `.env`, levantá el stack e instalá todo **dentro del contenedor** (Composer + `npm ci` + Vite build) y corregí permisos en el volumen montado:

```bash
./scripts/docker-install.sh
```

## Frontend (Vite / npm) en Docker

Solo recompilar assets (sin reinstalar):

```bash
./scripts/docker-vite-build.sh
```

 Equivale a `npm run build` dentro de `app_ciclo` y ajusta permisos de `node_modules` y `public/build`. Otros comandos npm:

```bash
./scripts/docker-npm.sh ci
./scripts/docker-npm.sh run dev
```

## Producción (Render) — recordatorios

- **Laravel Scheduler (CF4-163):** ver [docs/CRON_RENDER_LARAVEL.md](docs/CRON_RENDER_LARAVEL.md) (loop en `docker-entrypoint.sh`, heartbeat en `app_settings`, limitación Render Free).
- **`APP_URL`** y opcionalmente **`FRONTEND_URL`**: deben ser la URL pública HTTPS. Los **workers de cola** deben tener las mismas variables o los correos pueden generar enlaces a `localhost`.
- **`SESSION_DRIVER=database`**: requiere tabla `sessions` migrada (`database/migrations/0002_sessions.php`). Alternativa temporal: `SESSION_DRIVER=cookie`.
- **Google OAuth**: `GOOGLE_REDIRECT_URI` debe coincidir exactamente con la URI autorizada en Google Cloud Console (`{APP_URL}/auth/google/callback`).
- **Plazo “por recoger”**: configurable en horas (`READY_TO_PICKUP_EXPIRATION_HOURS` y ajuste en panel de pedidos).
- **Nota Jira**: correcciones de carrito / pedidos / notificaciones **no cierran CF4-72** (esa HU es sobre variantes de producto); ver `docs/CART_ORDER_FIXES_NOTE.md`.
