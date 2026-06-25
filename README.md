# 🛒 Plataforma Web de Catálogo, Inventario y Pedidos

Sistema web orientado a la digitalización de negocios comerciales que requieren mostrar productos en línea, administrar inventario, gestionar pedidos, controlar proveedores, registrar ventas y generar reportes administrativos.

El proyecto está diseñado como una solución adaptable para pequeñas y medianas empresas que desean modernizar sus procesos internos, mejorar su presencia digital y ofrecer una experiencia más clara y eficiente a sus clientes.

---

## 📌 Índice

- [Descripción general](#-descripción-general)
- [Objetivo del proyecto](#-objetivo-del-proyecto)
- [Problema que resuelve](#-problema-que-resuelve)
- [Adaptabilidad del sistema](#-adaptabilidad-del-sistema)
- [Características principales](#-características-principales)
- [Módulos del sistema](#-módulos-del-sistema)
- [Tecnologías utilizadas](#-tecnologías-utilizadas)
- [Arquitectura general](#-arquitectura-general)
- [Requisitos previos](#-requisitos-previos)
- [Instalación local](#-instalación-local)
- [Variables de entorno](#-variables-de-entorno)
- [Comandos útiles](#-comandos-útiles)
- [Pruebas](#-pruebas)
- [Despliegue](#-despliegue)
- [Estructura del proyecto](#-estructura-del-proyecto)
- [Seguridad](#-seguridad)
- [Flujo principal del sistema](#-flujo-principal-del-sistema)
- [Estado del proyecto](#-estado-del-proyecto)
- [Documentación relacionada](#-documentación-relacionada)
- [Licencia](#-licencia)

---

## 🧾 Descripción general

Esta plataforma web permite a un negocio comercial contar con un catálogo digital, un sistema de pedidos, un panel administrativo y herramientas internas para controlar productos, inventario, proveedores, ventas y reportes.

El sistema está pensado para empresas que actualmente manejan sus operaciones de forma manual, mediante hojas físicas, archivos dispersos, mensajes por aplicaciones de comunicación o registros poco centralizados.

La solución permite centralizar la información, reducir errores operativos, mejorar la atención al cliente y facilitar la toma de decisiones mediante datos actualizados.

---

## 🎯 Objetivo del proyecto

Desarrollar una aplicación web funcional, modular y adaptable que permita:

- Publicar productos o servicios en un catálogo digital.
- Consultar precios, imágenes, categorías y disponibilidad.
- Permitir pedidos en línea con retiro, entrega o gestión posterior.
- Administrar inventario de forma centralizada.
- Registrar ventas y movimientos relevantes.
- Gestionar proveedores.
- Controlar usuarios y roles.
- Generar reportes administrativos.
- Mejorar la presencia digital del negocio.
- Reducir errores derivados del manejo manual de información.
- Facilitar futuras ampliaciones del sistema.

---

## 🧩 Problema que resuelve

Muchos negocios pequeños y medianos operan con procesos manuales o poco integrados, lo que puede provocar:

- Inventario desactualizado.
- Dificultad para consultar productos disponibles.
- Pérdida de tiempo respondiendo consultas repetitivas.
- Errores en pedidos o cantidades.
- Registro manual de ventas.
- Falta de reportes confiables.
- Dependencia excesiva de una sola persona para administrar información.
- Baja presencia digital frente a competidores.

Esta plataforma busca resolver esos problemas mediante una solución web centralizada, accesible y escalable.

---

## 🔄 Adaptabilidad del sistema

El sistema fue diseñado para ser adaptable a distintos tipos de negocios que necesiten publicar productos, gestionar inventario y recibir pedidos.

Puede adaptarse a sectores como:

- Tiendas de repuestos.
- Ferreterías.
- Tiendas deportivas.
- Tiendas de bicicletas.
- Comercios de accesorios.
- Tiendas de tecnología.
- Negocios de ropa o calzado.
- Librerías.
- Tiendas de productos agrícolas.
- Catálogos empresariales internos.
- Negocios con pedidos para retiro en local.

La estructura modular permite reutilizar o modificar componentes según las necesidades de cada organización.

Ejemplos de adaptación:

- Cambiar el tipo de producto administrado.
- Agregar nuevas categorías o atributos.
- Modificar los estados de pedido.
- Cambiar el flujo de entrega o retiro.
- Agregar métodos de pago.
- Integrar servicios externos.
- Adaptar el panel administrativo a distintos roles.
- Reutilizar el sistema como base para otros catálogos comerciales.

---

## ✨ Características principales

### Para clientes

- Visualización de catálogo público.
- Búsqueda y filtrado de productos.
- Vista detallada de productos.
- Carrito de compras o lista de pedido.
- Confirmación de pedidos.
- Registro e inicio de sesión.
- Consulta de historial de pedidos.
- Gestión de productos favoritos.
- Interfaz adaptable a móvil, tablet y escritorio.
- Formulario de contacto o comunicación con el negocio.

### Para administradores

- Panel administrativo protegido.
- Gestión de productos.
- Gestión de categorías y subcategorías.
- Control de inventario.
- Gestión de pedidos.
- Actualización de estados.
- Gestión de proveedores.
- Registro y consulta de ventas.
- Dashboard con indicadores principales.
- Visualización y generación de reportes.
- Gestión de usuarios y roles.
- Control de acciones relevantes del sistema.

---

## 🧱 Módulos del sistema

### 1. Catálogo público

Permite mostrar productos o servicios disponibles al cliente final de forma clara y organizada.

Incluye:

- Nombre del producto.
- Descripción.
- Precio.
- Imagen.
- Categoría.
- Subcategoría.
- Disponibilidad.
- Estado del producto.
- Etiquetas visuales como destacado, nuevo, disponible o agotado.

---

### 2. Búsqueda y filtros

Permite al usuario encontrar productos de forma rápida mediante:

- Búsqueda por nombre.
- Filtro por categoría.
- Filtro por subcategoría.
- Filtro por precio.
- Filtro por disponibilidad.
- Ordenamiento de resultados.
- Combinación de criterios de búsqueda.

---

### 3. Carrito y pedidos

El cliente puede agregar productos a un carrito o lista de pedido, modificar cantidades y confirmar la solicitud.

Características:

- Agregar productos.
- Modificar cantidades.
- Eliminar productos.
- Ver subtotal y total.
- Confirmar pedido.
- Generar número o registro de pedido.
- Consultar estado del pedido.

El sistema puede configurarse para distintos modelos de negocio:

- Pedido con retiro en tienda.
- Pedido con entrega local.
- Pedido sujeto a confirmación del administrador.
- Pedido sin pago en línea.
- Pedido con integración futura a pasarela de pago.

---

### 4. Panel administrativo

Área privada para el personal autorizado.

Desde este módulo se administra la operación interna del sistema.

Incluye funciones para:

- Crear productos.
- Editar productos.
- Desactivar productos.
- Revisar pedidos.
- Cambiar estados de pedidos.
- Consultar ventas.
- Revisar inventario.
- Gestionar proveedores.
- Visualizar reportes.
- Administrar usuarios.

---

### 5. Inventario

Permite controlar existencias y reducir errores derivados de registros manuales.

Funciones principales:

- Registro de productos.
- Control de cantidades disponibles.
- Alertas de stock bajo.
- Actualización de stock según pedidos o ventas.
- Historial de movimientos.
- Validación de disponibilidad antes de confirmar pedidos.
- Posibilidad de carga o actualización masiva de datos.

---

### 6. Proveedores

Módulo orientado a registrar y consultar información de proveedores.

Permite almacenar:

- Nombre del proveedor.
- Información de contacto.
- Productos asociados.
- Pedidos a proveedores.
- Historial de transacciones.
- Estado del proveedor.
- Observaciones administrativas.

---

### 7. Ventas y reportes

Permite consultar información relevante para la administración del negocio.

Incluye:

- Registro detallado de ventas.
- Filtros por fecha.
- Filtros por estado.
- Filtros por producto.
- Resumen de ingresos.
- Productos con mayor movimiento.
- Reportes administrativos.
- Exportación o preparación de información para análisis.

---

### 8. Usuarios y roles

Permite controlar el acceso al sistema según el tipo de usuario.

Roles base:

- Cliente.
- Administrador.
- Superadministrador.

Funciones:

- Registro de clientes.
- Inicio de sesión.
- Gestión de perfil.
- Gestión de usuarios administrativos.
- Protección de rutas privadas.
- Control de permisos según rol.

---

## 🛠️ Tecnologías utilizadas

### Backend

- PHP
- Laravel
- Eloquent ORM
- Laravel Migrations
- Laravel Seeders
- Laravel Middleware
- Laravel Policies
- Laravel Scheduler
- Laravel Queues

### Frontend

- React
- TypeScript
- Inertia.js
- Tailwind CSS
- Blade
- Vite

### Base de datos

- MySQL
- MariaDB

### Testing y calidad

- PHPUnit
- Pest
- Playwright
- Laravel Pint
- ESLint
- TypeScript

### Despliegue e infraestructura

- Render
- Vercel
- GitHub
- Variables de entorno
- Cron jobs
- Base de datos externa
- Certificado SSL/TLS

---

## 🧱 Arquitectura general

El sistema utiliza Laravel como backend principal, con vistas modernas mediante Inertia.js y React.

```txt
Cliente Web
   │
   ▼
React + Inertia + TypeScript
   │
   ▼
Laravel Routes
   │
   ▼
Laravel Controllers
   │
   ▼
Requests / Services / Policies
   │
   ▼
Models Eloquent
   │
   ▼
Base de Datos MySQL
```

La aplicación separa responsabilidades mediante:

- Rutas para definir accesos.
- Controladores para manejar solicitudes.
- Modelos para representar entidades del negocio.
- Migraciones para estructura de base de datos.
- Requests para validación de formularios.
- Policies y middleware para autorización.
- Componentes React reutilizables para la interfaz.
- Jobs, notifications y scheduler para tareas automáticas.

---

## 📋 Requisitos previos

Antes de instalar el proyecto, se requiere tener instalado:

- PHP 8.1 o superior.
- Composer.
- Node.js 18 o superior.
- npm.
- MySQL o MariaDB.
- Git.

Opcional:

- Docker.
- Laravel Herd.
- Mailpit.
- Playwright browsers.

---



## 🔐 Variables de entorno

Ejemplo base de configuración:

```env
APP_NAME="Plataforma Web de Catálogo e Inventario"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=platform_catalog_inventory
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="no-reply@example.com"
MAIL_FROM_NAME="${APP_NAME}"

FILESYSTEM_DISK=public
```

En producción se recomienda:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com
SESSION_SECURE_COOKIE=true
```

---

## 🧪 Comandos útiles

### Ejecutar servidor local

```bash
php artisan serve
```

### Ejecutar Vite

```bash
npm run dev
```

### Compilar assets para producción

```bash
npm run build
```

### Ejecutar migraciones

```bash
php artisan migrate
```

### Ejecutar migraciones desde cero con datos iniciales

```bash
php artisan migrate:fresh --seed
```

### Limpiar cachés

```bash
php artisan optimize:clear
```

### Crear enlace de storage

```bash
php artisan storage:link
```

### Ejecutar colas

```bash
php artisan queue:work
```

### Ejecutar scheduler manualmente

```bash
php artisan schedule:run
```

### Optimizar aplicación para producción

```bash
php artisan optimize
```

---

## ✅ Pruebas

### Pruebas backend

```bash
php artisan test
```

O con Pest:

```bash
./vendor/bin/pest
```

---

### Pruebas frontend / E2E

```bash
npx playwright test
```

---

### Formato de código PHP

```bash
./vendor/bin/pint
```

---

### Revisión TypeScript

```bash
npm run typecheck
```

---

### Build de producción

```bash
npm run build
```

---

## 🚀 Despliegue

El proyecto puede desplegarse en plataformas como **Render**, **Vercel** o servidores VPS compatibles con Laravel.

---

### Recomendaciones para producción

Antes de desplegar:

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan optimize
php artisan storage:link
```

---

### Configuración recomendada

En producción se recomienda configurar:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://dominio-produccion.com`
- Base de datos externa.
- Certificado SSL activo.
- Variables de entorno protegidas.
- Worker activo para colas.
- Scheduler configurado.
- Backups programados.
- Logs monitoreados.

---

### Scheduler

Para ejecutar tareas programadas de Laravel:

```bash
php artisan schedule:run
```

En producción se recomienda configurar un cron job:

```bash
* * * * * php /ruta-del-proyecto/artisan schedule:run >> /dev/null 2>&1
```

---

### Queue Worker

Para procesar jobs en segundo plano:

```bash
php artisan queue:work --tries=3
```

---

## 📁 Estructura del proyecto

```txt
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Models/
├── Policies/
├── Services/
├── Jobs/
└── Notifications/

database/
├── migrations/
├── seeders/
└── factories/

resources/
├── js/
│   ├── Components/
│   ├── Layouts/
│   ├── Pages/
│   └── types/
├── css/
└── views/

routes/
├── web.php
├── auth.php
└── console.php

public/
storage/
tests/
```

---

## 🔒 Seguridad

El sistema contempla medidas básicas y necesarias para proteger la información del negocio y de los usuarios.

Medidas principales:

- Autenticación de usuarios.
- Protección de rutas administrativas.
- Roles y permisos.
- Validación de formularios.
- Hash seguro de contraseñas.
- Protección CSRF.
- Control de acceso mediante middleware.
- Uso de variables de entorno para secretos.
- Certificado SSL/TLS en producción.
- Registro de acciones críticas.
- Separación entre entorno local y producción.

No se deben subir al repositorio:

- Archivos `.env`.
- Claves privadas.
- Credenciales de base de datos.
- Tokens de servicios externos.
- Backups con información real.
- Archivos temporales sensibles.

---

## 🔄 Flujo principal del sistema

```txt
Cliente entra al catálogo
        ↓
Busca o filtra productos
        ↓
Agrega productos al carrito o solicitud
        ↓
Confirma pedido
        ↓
Administrador recibe el pedido
        ↓
Administrador revisa disponibilidad
        ↓
Administrador prepara o gestiona el pedido
        ↓
Cliente retira, recibe o coordina el producto
        ↓
Sistema actualiza estado e inventario
```

---

## 🧾 Estados comunes de pedido

```txt
Recibido
En revisión
En preparación
Listo para retirar
Entregado
Cancelado
```

Estos estados pueden modificarse según el modelo de negocio.

---

## 📊 Estado del proyecto

Estado actual:

```txt
En desarrollo / versión académica funcional
```

Funciones implementadas o en proceso:

- Catálogo público.
- Autenticación.
- Panel administrativo.
- Gestión de productos.
- Gestión de pedidos.
- Carrito o solicitud de productos.
- Inventario.
- Proveedores.
- Reportes.
- Mejoras UX/UI.
- Pruebas funcionales.
- Despliegue.
- Documentación técnica.
- Manual de usuario.

---

## 📚 Documentación relacionada

El proyecto puede complementarse con documentación académica y técnica como:

- Documento de visión y alcance.
- Documento de reglas de negocio.
- Especificación de requisitos de software.
- Documento de casos de uso.
- Priorización de casos de uso.
- Requerimientos funcionales.
- Requerimientos no funcionales.
- Requerimientos de seguridad.
- Requerimientos de desempeño.
- Requerimientos de usabilidad.
- Requerimientos de robustez.
- Requerimientos de documentación.
- Manual de usuario.
- Evidencias de pruebas.
- Evidencias de despliegue.

---

## 🧾 Convenciones de desarrollo

### Ramas sugeridas

```txt
main        → rama estable
develop     → rama de integración
feature/*   → nuevas funcionalidades
fix/*       → correcciones
hotfix/*    → correcciones urgentes
```

---

### Ejemplo de commits

```txt
feat: agregar módulo de proveedores
fix: corregir cálculo de stock en pedidos
docs: actualizar manual de instalación
refactor: limpiar controlador de productos
test: agregar pruebas para pedidos
style: mejorar diseño de catálogo
```

---

## 🐞 Reporte de errores

Para reportar errores se recomienda incluir:

- Descripción del problema.
- Pasos para reproducirlo.
- Resultado esperado.
- Resultado obtenido.
- Captura de pantalla, si aplica.
- Navegador o dispositivo utilizado.
- Usuario o rol con el que ocurrió el error.

---

## 📌 Notas importantes

- El sistema puede funcionar sin pagos en línea.
- Los métodos de pago pueden adaptarse según el negocio.
- El inventario debe mantenerse actualizado para evitar errores de disponibilidad.
- Las credenciales de producción deben manejarse únicamente mediante variables de entorno.
- El sistema requiere conexión a internet para operar correctamente.
- En producción debe mantenerse activo el worker de colas si se usan jobs.
- En producción debe configurarse el scheduler si existen tareas automáticas.
- Las imágenes y archivos públicos deben manejarse correctamente mediante storage.
- Se recomienda mantener respaldos periódicos de la base de datos.
- La solución puede extenderse para nuevos módulos o sectores comerciales.

---



## 🛒 Plataforma adaptable

Una solución web general para mejorar la presencia digital, la gestión interna y la atención al cliente de negocios comerciales.
