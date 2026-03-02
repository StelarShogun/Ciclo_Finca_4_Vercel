# Ciclo Finca — Sistema de Gestión

Aplicación web full-stack para la gestión de una tienda de ciclismo: catálogo público, carrito de compras, ventas, inventario, proveedores y panel de administración.

---

## a. Descripción del sistema

**Ciclo Finca** es un sistema de gestión que permite:

- **Lado público (clientes):**
  - Ver inicio y catálogo de productos (bicicletas, componentes, accesorios, indumentaria, herramientas, seguridad, nutrición).
  - Ver detalle de producto y agregar al carrito.
  - Gestionar carrito (agregar, actualizar cantidades, eliminar).
  - Iniciar sesión con correo/contraseña o con Google/Facebook.

- **Lado administración (requiere autenticación):**
  - **Dashboard** con datos resumidos y gráficos, con exportación de reportes.
  - **Usuarios:** CRUD de usuarios (solo administradores).
  - **Productos e inventario:** alta, edición, listado, importación/exportación y eliminación.
  - **Proveedores:** gestión de proveedores.
  - **Ventas:** registro de ventas, completar, cancelar, reembolsar, imprimir e imprimir factura (PDF) y exportar.

La aplicación está desplegada como un único proyecto (frontend y backend en la misma URL).

---

## b. Instrucciones básicas de uso

### Requisitos

- **PHP** ≥ 8.2  
- **Composer**  
- **Node.js** y **npm** (para Vite y recursos frontend)  
- **MySQL** (o compatible; en producción se usa Aiven)

### Instalación local

1. Clonar el repositorio y entrar al directorio del proyecto.

2. Instalar dependencias PHP y generar `.env`:
   ```bash
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Configurar base de datos en `.env`:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=tu_host
   DB_PORT=3306
   DB_DATABASE=tu_base
   DB_USERNAME=tu_usuario
   DB_PASSWORD=tu_contraseña
   ```

4. Ejecutar migraciones y seeders (opcional):
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

5. Instalar dependencias frontend y compilar:
   ```bash
   npm install
   npm run build
   ```

6. Iniciar el servidor de desarrollo:
   ```bash
   php artisan serve
   ```
   Opcional (servidor + cola + logs + Vite):
   ```bash
   composer dev
   ```

La aplicación quedará disponible en `http://localhost:8000` (o el puerto que indique `php artisan serve`).

### Uso rápido

- **Público:** ir a la raíz `/` para inicio, `/catalog` para catálogo, `/cart` para el carrito, `/login` para iniciar sesión.
- **Admin:** tras iniciar sesión como administrador, acceder a `/dashboard`, `/inventory`, `/products`, `/suppliers`, `/sales` según permisos.

---

## c. Tecnologías utilizadas

| Área            | Tecnología |
|-----------------|------------|
| Backend         | PHP 8.2, Laravel 12 |
| Base de datos   | MySQL (Aiven en producción) |
| Frontend        | Blade, Tailwind CSS 4, Vite 7 |
| HTTP / UI       | Axios, SweetAlert2 |
| PDF             | Laravel DomPDF (facturas e informes) |
| Autenticación   | Laravel Auth, Laravel Socialite (Google, Facebook) |
| Herramientas    | Laravel Tinker, PHPUnit, Laravel Pint |

---

## d. Enlaces al frontend y backend desplegados

La aplicación se despliega como **una sola URL** (frontend y backend en el mismo servicio):

| Entorno   | URL |
|-----------|-----|
| **Aplicación desplegada (frontend + backend)** | [https://ciclo-finca-4-app-4ccw.onrender.com](https://ciclo-finca-4-app-4ccw.onrender.com) |

- **Frontend:** misma URL (interfaz pública y panel de administración).
- **Backend/API:** misma URL; los endpoints de API y rutas web están bajo ese dominio.

---

## Licencia

El framework Laravel es software de código abierto bajo la [licencia MIT](https://opensource.org/licenses/MIT).
