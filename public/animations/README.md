# CF4 · Ilustraciones y escenas de error / estados vacíos

Assets usados en páginas HTTP (`resources/views/errors/*.blade.php`) y en estados vacíos del cliente (carrito, catálogo).

## Ilustración 404 (bicicleta y piloto)

- **Archivo:** `public/images/errors/404-bike-illustration-orig.png`
- **Uso:** Escena `wrong_route` en **404**, **419** y búsqueda sin resultados del catálogo (mismo SVG `errors/partials/404-bike-svg.blade.php`). Licencia: arte de proyecto / derechos según origen del PNG suministrado al repo.

## Escenas SVG inline (`resources/views/errors/partials/scenes/`)

Gráficos vectoriales **originales** para Ciclo Finca 4 (taller, candado, paquete), con gradientes y paleta `--brand-*`.

**Referencias de estilo (no copiados literalmente):** ilustraciones tipo [DrawKit](https://drawkit.com) / [unDraw](https://undraw.co) (MIT) como guía de proporción y materiales.

## Carrito vacío (tienda)

Estado vacío en `client/cart.blade.php`: bloque **`.cart-empty`** con icono Font Awesome (`fa-cart-shopping`), sin GSAP ni `state-card`.

## GSAP

Animaciones en `resources/js/errors/scenes.js` y módulos bajo `resources/js/errors/scenes/`.  
**GSAP** es software comercial con licencia que permite uso en este tipo de proyecto; revisar [https://gsap.com/licensing](https://gsap.com/licensing).
