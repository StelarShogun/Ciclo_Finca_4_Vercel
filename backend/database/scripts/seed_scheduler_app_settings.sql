-- CF4-163 — Scheduler monitoring keys in app_settings (production / DBeaver)
--
-- Safe to run multiple times: only inserts keys that do not exist yet.
-- Does not overwrite values written by scheduler:heartbeat or SchedulerMonitor.
--
-- After deploy, wake the Render web service and confirm scheduler_last_heartbeat_at
-- updates every ~minute. See docs/CRON_RENDER_LARAVEL.md

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_last_heartbeat_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_last_heartbeat_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_delete_expired_last_started_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_delete_expired_last_started_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_delete_expired_last_success_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_delete_expired_last_success_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_delete_expired_last_failure_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_delete_expired_last_failure_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_delete_expired_last_status', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_delete_expired_last_status');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_send_expiry_reminders_last_started_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_send_expiry_reminders_last_started_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_send_expiry_reminders_last_success_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_send_expiry_reminders_last_success_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_send_expiry_reminders_last_failure_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_send_expiry_reminders_last_failure_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_sales_send_expiry_reminders_last_status', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_sales_send_expiry_reminders_last_status');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_orders_cancel_expired_ready_last_started_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_orders_cancel_expired_ready_last_started_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_orders_cancel_expired_ready_last_success_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_orders_cancel_expired_ready_last_success_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_orders_cancel_expired_ready_last_failure_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_orders_cancel_expired_ready_last_failure_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_orders_cancel_expired_ready_last_status', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_orders_cancel_expired_ready_last_status');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_reports_send_weekly_dashboard_last_started_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_reports_send_weekly_dashboard_last_started_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_reports_send_weekly_dashboard_last_success_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_reports_send_weekly_dashboard_last_success_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_reports_send_weekly_dashboard_last_failure_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_reports_send_weekly_dashboard_last_failure_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_reports_send_weekly_dashboard_last_status', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_reports_send_weekly_dashboard_last_status');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_cf4_cleanup_temp_product_images_last_started_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_cf4_cleanup_temp_product_images_last_started_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_cf4_cleanup_temp_product_images_last_success_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_cf4_cleanup_temp_product_images_last_success_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_cf4_cleanup_temp_product_images_last_failure_at', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_cf4_cleanup_temp_product_images_last_failure_at');

INSERT INTO app_settings (`key`, `value`, created_at, updated_at)
SELECT 'scheduler_cf4_cleanup_temp_product_images_last_status', NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP()
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE `key` = 'scheduler_cf4_cleanup_temp_product_images_last_status');

-- Verify:
-- SELECT `key`, `value`, updated_at FROM app_settings WHERE `key` LIKE 'scheduler_%' ORDER BY `key`;
