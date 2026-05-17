# Dockerización y despliegue rápido (Ciclo finca #4)

**Guía paso a paso en Windows (PowerShell):** [GUIA_EJECUCION_CICLO_FINCA.md](GUIA_EJECUCION_CICLO_FINCA.md) — incluye eliminar contenedores viejos, crear `.env`, y todos los comandos en orden.

Todo el entorno (Laravel, MySQL, phpMyAdmin) se levanta con Docker Compose tal como está en esta carpeta.

## Requisitos

- Docker Desktop (o Docker + Docker Compose) instalado y en ejecución.
- Puertos libres en el host: **8080** (app), **8083** (phpMyAdmin), **3307** (MySQL del contenedor; el 3306 del host suele estar ocupado).

## Pasos para que funcione

### 0. Crear y configurar `.env`

En la raíz de **Ciclo finca#4** (esta carpeta):

```bash
copy .env.example .env
```

Edita `.env` y define valores reales para la base de datos (el `docker-compose.yml` los usa):

- `DB_DATABASE`: nombre de la base de datos (ej. `ciclo_finca`)
- `DB_USERNAME`: usuario de MySQL (ej. `laravel`)
- `DB_PASSWORD`: contraseña de ese usuario

No cambies `DB_HOST` en el `.env`: dentro del contenedor el compose inyecta `DB_HOST=db` automáticamente.

### 1. Construir y levantar los contenedores

```bash
docker compose up --build -d
```

Esto levanta: **app_ciclo** (Laravel en **8080**), **db_ciclo** (MySQL en el host **3307**), **phpmyadmin_ciclo** (**8083**).

### 2. Instalar dependencias de PHP dentro del contenedor

```bash
docker compose exec app_ciclo composer install
```

### 3. Generar la clave de aplicación

```bash
docker compose exec app_ciclo php artisan key:generate
```

### 4. Ejecutar migraciones y seeders

```bash
docker compose exec app_ciclo php artisan migrate --seed
```

(Si este proyecto tiene el comando `db:setup`, puedes usar en su lugar: `docker compose exec app php artisan db:setup`.)

### 5. URLs de acceso

| Servicio     | URL |
|-------------|-----|
| Aplicación  | http://localhost:8080 |
| phpMyAdmin  | http://localhost:8083 |

- **phpMyAdmin:** usuario `DB_USERNAME` y contraseña `DB_PASSWORD` de tu `.env`, o usuario `root` con contraseña `root` (según el `docker-compose.yml`).

## Troubleshooting común

- **Error 500:** revisa permisos de `storage` y `bootstrap/cache`:
  ```bash
  docker compose exec app chmod -R 775 storage bootstrap/cache
  docker compose exec app chown -R www-data:www-data storage bootstrap/cache
  ```
- **Falta la clave de aplicación:** en `.env` pon `APP_KEY=` y ejecuta `docker compose exec app php artisan key:generate`.
- **Connection refused a la base de datos:** espera unos segundos tras `docker compose up -d` (MySQL tarda en iniciar) y vuelve a intentar; o revisa que `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` en `.env` coincidan con lo que usa el servicio `db` en el compose.
- **Ver logs de Laravel:**
  ```bash
  docker compose exec app tail -f storage/logs/laravel.log
  ```
- **Parar todo:** `docker compose down`. Para borrar también la base de datos: `docker compose down -v`.

# Sistema de Gestión de Inventario, Ventas y Proveedores

## Descripción
Sistema unificado para la gestión de inventario de productos, ventas y proveedores desarrollado en Laravel.

## Características
- ✅ Gestión de inventario de productos
- ✅ Gestión de proveedores
- ✅ Sistema de ventas con facturación
- ✅ Dashboard con KPIs
- ✅ Exportación de datos (CSV, PDF, JSON, XML)
- ✅ Importación de productos
- ✅ Interfaz responsive

## Requisitos
- PHP 8.2 o superior
- Composer
- SQLite (incluido) o MySQL/PostgreSQL
- Node.js y NPM (para assets)

## Instalación

### 1. Clonar el repositorio
```bash
git clone <url-del-repositorio>
cd modulo-productos
```

### 2. Instalar dependencias
```bash
composer install
npm install
```

### 3. Configurar variables de entorno
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Configurar base de datos
```bash
# Para SQLite (recomendado para desarrollo)
touch database/database.sqlite

# O configurar MySQL/PostgreSQL en .env
```

### 5. Ejecutar migraciones y seeders
```bash
php artisan db:setup
```

Este comando ejecutará:
- Migraciones para crear todas las tablas
- Seeders para poblar la base de datos con datos de prueba

### 6. Compilar assets (opcional)
```bash
npm run build
```

### 7. Iniciar servidor
```bash
php artisan serve
```

## Usuarios de prueba
- **Administrador**: admin@cicloperez.com (password: Admin2024!@#)
- **Administrador (alternativo)**: admin@example.com (password: password)
- **Usuario 1**: juan@example.com (password: password)
- **Usuario 2**: maria@example.com (password: password)

## Estructura del proyecto

### Módulos principales
- **Inventario**: Gestión de productos, categorías y stock
- **Proveedores**: Gestión de proveedores y sus datos
- **Ventas**: Sistema de ventas con facturación automática

### Base de datos
- `users`: Usuarios del sistema
- `categorias`: Categorías de productos
- `proveedores`: Información de proveedores
- `productos`: Catálogo de productos
- `ventas`: Registro de ventas
- `detalle_ventas`: Detalles de cada venta

### Rutas principales
- `/inventory` - Gestión de inventario
- `/proveedores` - Gestión de proveedores
- `/ventas` - Gestión de ventas

## Comandos útiles

### CI / calidad antes de push (rama `Dev`)

GitHub Actions ejecuta los mismos checks que en local. Ver guía completa: [CI.md](CI.md).

```bash
docker compose exec app_ciclo composer run check
docker compose exec app_ciclo npm ci && npm run build
```

### Limpiar base de datos
```bash
php artisan db:clean
```

### Configurar base de datos desde cero
```bash
php artisan db:setup
```

### Ejecutar migraciones individuales
```bash
php artisan migrate
```

### Ejecutar seeders individuales
```bash
php artisan db:seed
```

## Funcionalidades

### Inventario
- CRUD de productos
- Gestión de categorías
- Control de stock
- Importación/exportación de datos
- Filtros y búsqueda avanzada

### Proveedores
- CRUD de proveedores
- Evaluación de proveedores
- Gestión de tiempos de entrega
- Información de contacto

### Ventas
- Creación de ventas
- Facturación automática
- Gestión de estados de venta
- Cálculo automático de IVA
- Exportación de reportes

## Tecnologías utilizadas
- Laravel 11
- PHP 8.2+
- SQLite/MySQL/PostgreSQL
- Bootstrap 5
- Font Awesome
- Chart.js (para gráficos)
- DomPDF (para PDFs)

## Contribución
1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## Licencia
Este proyecto está bajo la Licencia MIT.
