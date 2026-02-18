# Guía de ejecución – Ciclo finca #4 con Docker Compose

Sigue estos pasos **en orden** en PowerShell. Los contenedores que tenías para "proyecto principal unificado" (ciclo-mysql, ciclo-phpmyadmin, etc.) se eliminan primero para que no ocupen puertos; luego se usa solo Docker Compose de esta carpeta.

---

## Paso 1: Detener y eliminar contenedores viejos (opcional)

Si antes creaste contenedores a mano para proyecto principal unificado, deténlos y bórralos para liberar puertos 8080, 8081 y 3306:

```powershell
docker stop ciclo-mysql ciclo-phpmyadmin 2>$null
docker rm ciclo-mysql ciclo-phpmyadmin 2>$null
docker network rm ciclo-net 2>$null
```

Si no tenías esos contenedores, los comandos fallarán sin problema. Sigue al paso 2.

---

## Paso 2: Ir a la carpeta del proyecto

```powershell
cd "c:\Users\USUARIO\Documents\UNA\UNA tercer año I Ciclo\documentos inge\Proyecto-INGESIS\Ciclo finca#4"
```

---

## Paso 3: Crear el archivo `.env`

Si **no** tienes un archivo `.env` en esta carpeta:

```powershell
copy .env.example .env
```

Abre `.env` con el bloc de notas (o tu editor) y **cambia** estas tres líneas con valores reales:

- `DB_DATABASE=nombre_base_de_datos`  → por ejemplo: `DB_DATABASE=ciclo_finca`
- `DB_USERNAME=usuario`               → por ejemplo: `DB_USERNAME=laravel`
- `DB_PASSWORD=contraseña`            → por ejemplo: `DB_PASSWORD=MiPassword123`

Guarda el archivo. No cambies `DB_HOST`: Docker Compose inyecta `DB_HOST=db` dentro del contenedor.

---

## Paso 4: Construir y levantar los contenedores

```powershell
docker compose up --build -d
```

Espera a que termine (puede tardar un poco la primera vez). Deberías ver los tres servicios: **app**, **db**, **phpmyadmin**.

---

## Paso 5: Instalar dependencias de PHP (Composer)

```powershell
docker compose exec app composer install
```

---

## Paso 6: Generar la clave de la aplicación

```powershell
docker compose exec app php artisan key:generate
```

---

## Paso 7: Crear tablas y datos de prueba (migraciones + seeders)

```powershell
docker compose exec app php artisan migrate --seed
```

---

## Paso 8: Probar la aplicación

- **Aplicación Laravel:** http://localhost:8080  
- **phpMyAdmin:** http://localhost:8081  
  - Usuario: el que pusiste en `DB_USERNAME` (ej. `laravel`)  
  - Contraseña: la que pusiste en `DB_PASSWORD`  
  - O usuario `root` con contraseña `root` (según el docker-compose de este proyecto)

Usuarios de prueba de la app (según el README del proyecto):  
- admin@cicloperez.com / Admin2024!@#  
- admin@example.com / password  

---

## Resumen de comandos (copiar y pegar en bloque)

```powershell
docker stop ciclo-mysql ciclo-phpmyadmin 2>$null
docker rm ciclo-mysql ciclo-phpmyadmin 2>$null
docker network rm ciclo-net 2>$null

cd "c:\Users\USUARIO\Documents\UNA\UNA tercer año I Ciclo\documentos inge\Proyecto-INGESIS\Ciclo finca#4"

copy .env.example .env
# Edita .env y pon DB_DATABASE, DB_USERNAME, DB_PASSWORD

docker compose up --build -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

Luego abre http://localhost:8080 y http://localhost:8081.

---

## Si algo falla

- **Puerto en uso:** otro programa usa 8080, 8081 o 3306. Cierra esa aplicación o cambia los puertos en `docker-compose.yml`.
- **Error 500 en la app:** ejecuta  
  `docker compose exec app chmod -R 775 storage bootstrap/cache`  
  y  
  `docker compose exec app chown -R www-data:www-data storage bootstrap/cache`
- **No conecta a la base de datos:** espera 30–60 segundos después de `docker compose up -d` (MySQL tarda en iniciar) y vuelve a intentar. Revisa que `.env` tenga bien `DB_DATABASE`, `DB_USERNAME` y `DB_PASSWORD`.
- **Parar todo:** `docker compose down`  
  **Parar y borrar la base de datos:** `docker compose down -v`
