# Solución: Error 500 y phpMyAdmin no funciona

Los errores **no son por los cambios en el módulo de ventas**. El log de Laravel indica:

1. **500 Server Error:** `No application encryption key has been specified` → Falta `APP_KEY` en el archivo `.env`.
2. **Base de datos:** La app puede estar usando la base por defecto `laravel` en lugar de la tuya (ej. `ciclo_finca`), o el `.env` no se está leyendo bien dentro del contenedor.
3. **phpMyAdmin:** Si los contenedores no arrancan bien o el `.env` no tiene la base de datos correcta, phpMyAdmin también puede fallar.

---

## Pasos para solucionar (en orden)

### 1. Asegurarte de que Docker esté en ejecución

- Abre **Docker Desktop** y espera a que esté listo.

### 2. Crear o revisar el archivo `.env` en la carpeta **Ciclo finca#4**

En la raíz de **Ciclo finca#4** (donde está `docker-compose.yml`) debe existir un archivo llamado **`.env`**. Si no existe, créalo a partir de `.env.example`:

```powershell
cd "Ciclo finca#4"
copy .env.example .env
```

Abre `.env` y deja algo como esto (ajusta si ya usabas otros valores):

```env
APP_NAME="Ciclo Finca"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ciclo_finca
DB_USERNAME=laravel
DB_PASSWORD=laravel123

CACHE_STORE=file
```

Importante:

- **`APP_KEY=`** debe estar vacío aquí; lo generaremos en el paso 4.
- **`DB_HOST=db`** no lo cambies (es el nombre del servicio en Docker).
- **`DB_DATABASE`** debe ser el nombre de la base que usa MySQL (igual que en la guía que te funcionaba antes, ej. `ciclo_finca`).
- **`CACHE_STORE=file`** evita que Laravel use la tabla `cache` en la base de datos y así no depender de que esa tabla exista.

Guarda el archivo.

### 3. Levantar o reiniciar los contenedores

En la misma carpeta **Ciclo finca#4**:

```powershell
docker compose down
docker compose up -d
```

Espera unos 20–30 segundos para que MySQL arranque.

### 4. Generar la clave de aplicación **dentro** del contenedor

Esto es lo que quita el error 500 de “No application encryption key”:

```powershell
docker compose exec app php artisan key:generate
```

Ese comando escribe `APP_KEY=base64:...` en tu `.env` automáticamente.

### 5. Crear la base de datos y tablas (si hace falta)

Si la base `ciclo_finca` (o la que pusiste en `DB_DATABASE`) no existe o está vacía:

```powershell
docker compose exec app php artisan migrate --seed
```

Si te pide confirmación, escribe `yes`.

### 6. Probar de nuevo

- **Laravel:** Abre en el navegador: **http://localhost:8080**
- **phpMyAdmin:** Abre: **http://localhost:8081**  
  - Usuario: el de `DB_USERNAME` (ej. `laravel`)  
  - Contraseña: la de `DB_PASSWORD` (ej. `laravel123`)

---

## Si phpMyAdmin sigue sin funcionar

- Comprueba que el contenedor esté arriba:
  ```powershell
  docker compose ps
  ```
  Deberías ver los servicios `app`, `db` y `phpmyadmin` en estado “Up”.

- Si **phpMyAdmin** da error de conexión, revisa que en `.env` tengas:
  - `DB_DATABASE=ciclo_finca` (o el nombre que uses)
  - `DB_USERNAME=laravel`
  - `DB_PASSWORD=laravel123`  
  y que hayas ejecutado `docker compose up -d` **después** de guardar el `.env`.

- Si **8081** ya lo usa otro programa, en `docker-compose.yml` puedes cambiar:
  ```yaml
  ports:
    - "8082:80"   # entonces phpMyAdmin será http://localhost:8082
  ```

---

## Resumen rápido

| Problema              | Causa probable              | Solución                                      |
|----------------------|-----------------------------|-----------------------------------------------|
| 500 Server Error     | Falta `APP_KEY` en `.env`   | `docker compose exec app php artisan key:generate` |
| Tabla cache no existe| Cache en base de datos      | En `.env` pon `CACHE_STORE=file`             |
| phpMyAdmin no abre   | Contenedores o `.env`       | `docker compose up -d`, revisar `DB_*` en `.env` |

Después de estos pasos, la aplicación y phpMyAdmin deberían funcionar sin depender de los cambios hechos en el módulo de ventas.
