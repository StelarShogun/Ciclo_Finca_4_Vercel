# CF4-68 — Respaldo para merge con `Dev`

Este documento resume **todo lo implementado para la HU CF4-68 (subcategorías)** en la rama `feature/CF4-68-create-subcategories`, para que al fusionar `Dev` (cambios de rutas JS, carpetas y Vite) puedas **reconciliar conflictos** sin perder lógica ni UI.

**Rama:** `feature/CF4-68-create-subcategories`  
**Último commit documentado (HEAD al generar esto):** `eb7c6f7`

---

## 1. Commits relevantes (orden cronológico, línea principal)

Desde relaciones en `Category` hasta ajustes de UI responsive:

| Hash | Mensaje |
|------|---------|
| `d454db5` | feat(CF4-68): add Category parent() and children() relations |
| `29d8a79` | feat(CF4-68): validate and store subcategories in CategoryController |
| `c780173` | Merge remote-tracking branch 'origin/Dev' into feature/CF4-68-create-subcategories |
| `e849939` | chore(CF4-68): start subcategory UI work *(commit vacío de marcador)* |
| `514ec84` | chore: checkpoint before CF4-68 step 3 *(commit vacío de marcador)* |
| `9cc786b` | feat(CF4-68): add admin subcategory create form |
| `0e8b278` | fix: align admin asset paths and simplify header button styles |
| `a4a391b` | fix(CF4-68): normalize category selectors and Vite inputs |
| `c110c31` | feat(inventory): CF4-68 subcategories, scoped product name validation, admin UX |
| `b2edfe0` | feat(inventory): show category breadcrumb in list and details modal |
| `eb7c6f7` | fix(inventory-ui): improve header button responsiveness and filter label |

**Nota:** `c780173` incorporó cambios de otras historias (p. ej. refactor frontend). Los archivos “críticos” de CF4-68 para el merge están listados abajo.

---

## 2. Archivos tocados por la HU (lista única para conservar)

Unión de los cambios de los commits anteriores (sin contar solo el merge masivo entero):

| Archivo | Rol en CF4-68 |
|---------|----------------|
| `app/Models/Category.php` | Relaciones `parent()` / `children()` (y alias legacy). |
| `app/Http/Controllers/CategoryController.php` | Crear/listar subcategorías, validación `unique` por padre, vistas de apoyo. |
| `app/Http/Controllers/ProductController.php` | Inventario: filtros `parent_category_id` / `subcategory_id`, eager `category.parent`, JSON `show` con padre, mensaje update en español, export con nombre categoría (según versión). |
| `app/Http/Requests/StoreProductRequest.php` | `unique` de nombre **acotado por `category_id`**. |
| `app/Http/Requests/UpdateProductRequest.php` | `unique` por categoría + `ignore` del `product_id` (ruta + segmentos URL). |
| `routes/web.php` | Rutas GET/POST subcategorías admin; grupo admin. |
| `resources/views/products/inventory.blade.php` | Filtros padre/subcategoría, modales crear/editar con selects dependientes, enlace “Crear Subcategoría”, breadcrumb en tabla/grid, label filtro “Categoría”, `@vite` inventario. |
| `resources/views/categories/subcategories/create.blade.php` | Formulario crear subcategoría + jerarquía. |
| `resources/js/admin/inventory.js` | Selects dependientes, `categoryPath()` en modal detalle, `jsonValidationMessage`, guardar/editar AJAX. |
| `resources/css/buttons.css` | Botones header sin compresión (`flex`/`min-width`), responsive wrap/grid móvil. |
| `vite.config.js` | Entrada `resources/js/admin/inventory.js` (y otras entradas admin según commit `a4a391b`). |

**Archivos tocados en commits auxiliares (no son el núcleo CF4-68 pero pueden aparecer en conflictos con `Dev`):**

- `0e8b278`: `resources/css/dashboard.css`, `resources/views/admin/login/admin_login.blade.php`, `resources/views/client/layouts/admin_auth.blade.php`, `resources/views/client/layouts/guest.blade.php`

---

## 3. Comportamiento funcional a no perder

### Backend

- Subcategoría = fila en `categories` con `parent_category_id` no nulo.
- Validación de nombre de subcategoría **única por mismo padre** (no global duplicado inútil).
- Producto sigue guardando solo `category_id` (puede ser raíz o hijo).
- **Nombre de producto único por `category_id`** (subcategoría cuenta como categoría distinta), con `ignore` correcto al editar.
- Listado inventario: eager `category.parent` para breadcrumb y modal.

### Frontend inventario

- Filtros: categoría (raíz) + subcategoría dependiente; query params `parent_category_id`, `subcategory_id`.
- Crear/editar producto: select padre + subcategoría + hidden `category_id` final.
- Vista tabla y grid: texto **breadcrumb** `Padre > Hijo` cuando aplica.
- Modal ver producto: misma cadena vía `categoryPath(product.category)` en JS (requiere JSON con `category.parent`).
- SweetAlert: errores 422 con texto legible desde `errors`.
- Header: botones sin texto cortado; responsive tablet/móvil.

---

## 4. Tras merge con `Dev` — checklist

1. **`vite.config.js`:** que exista la entrada de `resources/js/admin/inventory.js` (o la ruta que use `Dev`).
2. **`resources/views/products/inventory.blade.php`:** `@vite([...])` apunte al CSS/JS correctos tras el refactor.
3. **`public/build/manifest.json`:** ejecutar `npm run build` (idealmente **dentro del contenedor** si en host hay permisos rotos).
4. **Rutas:** `categories.subcategories.create` / `categories.subcategories.store` presentes en `routes/web.php`.
5. **Probar:** crear subcategoría → asignar producto → editar sin cambiar nombre → filtros → modal detalle (breadcrumb).

---

## 5. Recuperar esta HU desde Git (si algo se pierde en el merge)

En la rama donde quieras traer solo estos commits (ajusta rangos si hace falta):

```bash
# Ver solo estos commits
git log d454db5..eb7c6f7 --oneline

# Opción A: cherry-pick del rango (puede requerir resolver conflictos)
git cherry-pick d454db5^..eb7c6f7
```

Si el merge ya mezcló mal, otra opción es **sacar archivos concretos** desde la rama feature:

```bash
git checkout feature/CF4-68-create-subcategories -- path/al/archivo
```

---

## 6. Referencia rápida: nombres en UI

- **Breadcrumb:** en inglés se dice *breadcrumb* (“migas de pan”): `Categoría padre > Subcategoría`.

---

*Generado para respaldo antes de fusionar `Dev` en la rama de trabajo CF4-68.*
