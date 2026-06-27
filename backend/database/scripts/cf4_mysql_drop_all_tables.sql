-- CF4 — Teardown MySQL (run before `php artisan migrate` / `migrate:fresh`).
-- Order: disable FK checks, drop every app table, re-enable checks.
-- Does NOT drop the database itself (keeps grants and DB name).

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `sale_items`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `sales`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `classification_product`;
DROP TABLE IF EXISTS `classification_values`;
DROP TABLE IF EXISTS `classification_dimensions`;
DROP TABLE IF EXISTS `products_brand`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `media`;
DROP TABLE IF EXISTS `app_settings`;
DROP TABLE IF EXISTS `client_password_reset_tokens`;
DROP TABLE IF EXISTS `client_table`;
DROP TABLE IF EXISTS `admins`;
DROP TABLE IF EXISTS `brands`;
DROP TABLE IF EXISTS `suppliers`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `migrations`;
DROP TABLE IF EXISTS `sessions`;
DROP TABLE IF EXISTS `cache_locks`;
DROP TABLE IF EXISTS `cache`;

SET FOREIGN_KEY_CHECKS = 1;

-- Next: from project root, `php artisan migrate` or `php artisan migrate:fresh --seed`.
