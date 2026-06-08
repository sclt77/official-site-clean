CREATE TABLE IF NOT EXISTS `site_limit_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(64) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `requested_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `unit_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pay_type` VARCHAR(30) NOT NULL DEFAULT 'alipay',
  `trade_no` VARCHAR(120) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_limit_orders_order_no` (`order_no`),
  KEY `idx_site_limit_orders_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_limit_request_enabled', '0'),
  ('auth_purchase_enabled', '0'),
  ('auth_purchase_price', '0.00'),
  ('auth_purchase_max', '10')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
