# Pruebas: vigencia y eliminación automática de pedidos

## 1. Alerta visual (icono de peligro + mini label)

- En la **lista de ventas**, si un pedido tiene **2 días o menos** para ser eliminado, verás el badge naranja con el icono **⚠️** (exclamation-triangle).
- **Pasa el ratón sobre el icono** (o haz foco con Tab): se mostrará una mini etiqueta con el texto:  
  *"¡Atención! Este pedido será eliminado automáticamente en X día(s). Realice las acciones necesarias antes de la fecha límite."*
- Lo mismo aplica en el **modal de detalle** al ver un pedido próximo a expirar.

## 2. Cómo probar la eliminación cuando se cumpla el tiempo

### Opción A: Reducir días de vigencia y ejecutar el comando a mano

1. En `.env` pon un valor bajo para que los pedidos “venzan” pronto:
   ```env
   ORDER_EXPIRATION_DAYS=1
   ```
2. (Opcional) Limpia la caché de config:
   ```bash
   php artisan config:clear
   ```
3. Crea ventas de prueba con el seeder (una será de “ayer”):
   ```bash
   php artisan db:seed --class=SalesSeeder
   ```
4. Ejecuta el comando que elimina pedidos vencidos:
   ```bash
   php artisan sales:delete-expired
   ```
5. Comprueba en la **lista de ventas** que los pedidos con `sale_date` anterior a “hoy menos 1 día” ya no aparecen (el índice solo muestra pedidos no expirados).

### Opción B: Simular que ya pasó el tiempo (solo para pruebas)

1. En la base de datos, cambia temporalmente la `sale_date` de una venta a una fecha antigua (por ejemplo, hoy menos 31 días).
2. Asegúrate de que `ORDER_EXPIRATION_DAYS=30` (o el valor que uses).
3. Ejecuta:
   ```bash
   php artisan sales:delete-expired
   ```
4. Esa venta debería desaparecer de la tabla y dejar de salir en la lista.

### Programación diaria (producción)

El comando está programado en `routes/console.php` para ejecutarse **una vez al día**:

```php
Schedule::command('sales:delete-expired')->daily();
```

En producción, el planificador de Laravel debe estar activo (p. ej. cron que ejecute `php artisan schedule:run` cada minuto).

## 3. Si el conteo de “días restantes” se actualiza dinámicamente

- **Sí se actualiza**, pero **cada vez que se vuelve a cargar la página** (lista de ventas o detalle de un pedido).
- Los “días restantes” se calculan en el servidor con la fecha/hora actual en cada petición, así que:
  - Al **recargar** la lista o al **abrir de nuevo** el detalle de un pedido, verás el número de días actualizado.
- No hay actualización en tiempo real sin recargar (por ejemplo, si dejas la lista abierta una hora, no verás el cambio hasta que recargues o vuelvas a entrar a la vista).

Para comprobarlo:

1. Abre la lista de ventas y anota los “días restantes” de un pedido.
2. Recarga la página más tarde (o al día siguiente): el valor debería haber bajado (o ser 0 y mostrarse como “Expirado”) según la nueva fecha.

---

## 4. Tests automáticos (CF4-20)

Los casos de prueba están en `tests/Feature/SalesOrderExpiryTest.php`. Cada método cubre un caso:

| Test | Caso de prueba |
|------|----------------|
| test_list_and_detail_show_exact_creation_date_time | CP1: Fecha y hora exacta en lista y detalle |
| test_system_calculates_days_remaining_until_expiration | CP2: Cálculo automático de días restantes |
| test_days_remaining_recalculated_on_each_request | CP3: Conteo actualizado en cada petición |
| test_alert_shown_when_two_days_or_less_remaining | CP4: Alerta visual cuando ≤2 días |
| test_expired_orders_are_deleted_by_command | CP5: Eliminación automática al superar el límite |
| test_newly_created_order_shows_full_days_remaining | CP6: Pedido nuevo muestra tiempo completo |
| test_order_near_limit_shows_reduced_days_remaining | CP7: Pedido cercano a límite muestra días reducidos |
| test_deleted_order_not_shown_in_active_list | CP8: Pedido eliminado no aparece en la lista |

**Requisitos:** MySQL (la tabla `sales` existe tras las migraciones de refactor). Con SQLite o sin driver de BD los tests se marcan como *skipped*.

**Ejecutar con MySQL:**

1. Crea `.env.testing` con `DB_CONNECTION=mysql` y `DB_DATABASE` (por ejemplo una BD solo para tests).
2. Ejecuta migraciones en esa BD: `php artisan migrate --env=testing`
3. Ejecuta: `php artisan test tests/Feature/SalesOrderExpiryTest.php`
