-- Create the cart_items table if it doesn't exist (one cart per customer).
-- Run on the ciclo_finca_4 database.

CREATE TABLE IF NOT EXISTS `cart_items` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `client_id` BIGINT UNSIGNED NOT NULL,
    `product_id` BIGINT UNSIGNED NOT NULL,
    `quantity` INT UNSIGNED NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    UNIQUE KEY `cart_items_client_id_product_id_unique` (`client_id`, `product_id`),
    CONSTRAINT `cart_items_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `client_table` (`user_id`) ON DELETE CASCADE,
    CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`product_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
