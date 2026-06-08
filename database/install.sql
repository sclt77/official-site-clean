CREATE TABLE IF NOT EXISTS `users` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(120) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `name` VARCHAR(50) DEFAULT NULL,
  `role` VARCHAR(20) NOT NULL DEFAULT 'user',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `site_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `claybbs_site_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `cutot_site_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `developer_level` VARCHAR(30) NOT NULL DEFAULT 'none',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 1,
  `email_verify_token` VARCHAR(64) DEFAULT NULL,
  `email_verify_expires_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_email_verify_token` (`email_verify_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `sites` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product` VARCHAR(20) NOT NULL DEFAULT 'claybbs',
  `site_id` VARCHAR(80) NOT NULL,
  `token` VARCHAR(120) NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `license_key` VARCHAR(64) DEFAULT NULL,
  `license_status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `license_type` VARCHAR(20) NOT NULL DEFAULT 'permanent',
  `license_expires_at` DATETIME DEFAULT NULL,
  `bound_at` DATETIME DEFAULT NULL,
  `last_seen_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sites_site_id` (`site_id`),
  UNIQUE KEY `uk_sites_license_key` (`license_key`),
  UNIQUE KEY `uk_sites_user_domain_product` (`user_id`, `domain`, `product`),
  KEY `idx_sites_user` (`user_id`),
  CONSTRAINT `fk_sites_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `packages` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `product` VARCHAR(20) NOT NULL DEFAULT 'claybbs',
  `type` VARCHAR(20) NOT NULL DEFAULT 'diff',
  `version` VARCHAR(50) NOT NULL,
  `from_version` VARCHAR(50) DEFAULT NULL,
  `branch` VARCHAR(50) NOT NULL DEFAULT 'main',
  `filename` VARCHAR(255) NOT NULL,
  `rollback_filename` VARCHAR(255) DEFAULT NULL,
  `full_filename` VARCHAR(255) DEFAULT NULL,
  `hash` VARCHAR(64) NOT NULL,
  `signature` TEXT DEFAULT NULL,
  `package_hash` VARCHAR(64) DEFAULT NULL,
  `package_signature` TEXT DEFAULT NULL,
  `full_hash` VARCHAR(64) DEFAULT NULL,
  `full_signature` TEXT DEFAULT NULL,
  `rollback_hash` VARCHAR(64) DEFAULT NULL,
  `rollback_signature` TEXT DEFAULT NULL,
  `manifest_json` MEDIUMTEXT DEFAULT NULL,
  `file_size` BIGINT UNSIGNED DEFAULT NULL,
  `full_file_size` BIGINT UNSIGNED DEFAULT NULL,
  `rollback_file_size` BIGINT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `has_code` TINYINT(1) NOT NULL DEFAULT 1,
  `has_db` TINYINT(1) NOT NULL DEFAULT 1,
  `status` VARCHAR(20) NOT NULL DEFAULT 'published',
  `update_level` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `force_update` TINYINT(1) NOT NULL DEFAULT 0,
  `min_version` VARCHAR(50) DEFAULT NULL,
  `max_version` VARCHAR(50) DEFAULT NULL,
  `full_key_used` TINYINT(1) NOT NULL DEFAULT 0,
  `full_key_used_site` VARCHAR(80) DEFAULT NULL,
  `full_key_used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_packages_type` (`type`),
  KEY `idx_packages_status` (`status`),
  KEY `idx_packages_branch` (`branch`),
  KEY `idx_packages_from_version` (`from_version`),
  KEY `idx_packages_update_level` (`update_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `full_keys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `package_id` BIGINT UNSIGNED NOT NULL,
  `full_key` VARCHAR(120) NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `used_site` VARCHAR(80) DEFAULT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_full_key` (`full_key`),
  KEY `idx_full_keys_pkg` (`package_id`),
  CONSTRAINT `fk_full_keys_pkg` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `download_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `site_id` VARCHAR(80) DEFAULT NULL,
  `package_id` BIGINT UNSIGNED NOT NULL,
  `kind` VARCHAR(20) NOT NULL DEFAULT 'package',
  `filename` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_dl_user` (`user_id`),
  KEY `idx_dl_pkg` (`package_id`),
  KEY `idx_dl_site` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `publish_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` VARCHAR(80) NOT NULL,
  `package_id` BIGINT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'success',
  `event` VARCHAR(50) DEFAULT NULL,
  `full_key` VARCHAR(120) DEFAULT NULL,
  `log` TEXT DEFAULT NULL,
  `from_version` VARCHAR(50) DEFAULT NULL,
  `to_version` VARCHAR(50) DEFAULT NULL,
  `kind` VARCHAR(20) DEFAULT NULL,
  `duration_ms` INT UNSIGNED DEFAULT NULL,
  `health_json` MEDIUMTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_publish_site` (`site_id`),
  KEY `idx_publish_pkg` (`package_id`),
  KEY `idx_publish_status` (`status`),
  KEY `idx_publish_to_version` (`to_version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `content` TEXT DEFAULT NULL,
  `level` VARCHAR(20) NOT NULL DEFAULT 'info',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_announce_status` (`status`),
  KEY `idx_announce_level` (`level`),
  KEY `idx_announce_sort` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `market_categories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(20) NOT NULL,
  `name` VARCHAR(80) NOT NULL,
  `slug` VARCHAR(80) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_market_category_type_slug` (`type`, `slug`),
  KEY `idx_market_category_type_status` (`type`, `status`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `market_categories` (`type`, `name`, `slug`, `sort_order`, `status`) VALUES
  ('plugin', '通用插件', 'general', 10, 'active'),
  ('theme', '通用主题', 'general', 10, 'active')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `sort_order` = VALUES(`sort_order`);

CREATE TABLE IF NOT EXISTS `market_items` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(20) NOT NULL,
  `category_id` BIGINT UNSIGNED DEFAULT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `name` VARCHAR(120) NOT NULL,
  `version` VARCHAR(50) NOT NULL DEFAULT '1.0.0',
  `description` TEXT DEFAULT NULL,
  `author` VARCHAR(120) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `logo` VARCHAR(255) DEFAULT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `hash` VARCHAR(128) NOT NULL,
  `manifest_json` MEDIUMTEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `developer_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `review_note` TEXT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `downloads` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_type_slug` (`type`, `slug`),
  KEY `idx_market_items_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `market_versions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `version` VARCHAR(50) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `hash` VARCHAR(128) NOT NULL,
  `manifest_json` MEDIUMTEXT DEFAULT NULL,
  `changelog` TEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `review_note` TEXT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_item_status` (`item_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `market_licenses` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `license_key` VARCHAR(80) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `order_id` BIGINT UNSIGNED DEFAULT NULL,
  `bound_domain` VARCHAR(190) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'active',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_license_key` (`license_key`),
  UNIQUE KEY `uk_user_item` (`user_id`, `item_id`),
  KEY `idx_market_licenses_order` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `market_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(64) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `developer_user_id` BIGINT UNSIGNED DEFAULT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `developer_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  `developer_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `platform_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `pay_type` VARCHAR(30) NOT NULL DEFAULT 'alipay',
  `trade_no` VARCHAR(120) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_market_orders_order_no` (`order_no`),
  KEY `idx_market_orders_user_status` (`user_id`, `status`),
  KEY `idx_market_orders_item` (`item_id`),
  KEY `idx_market_orders_developer` (`developer_user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `developer_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(64) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `trade_no` VARCHAR(120) DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `paid_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_developer_orders_order_no` (`order_no`),
  KEY `idx_developer_orders_user_status` (`user_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `developer_withdrawals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `account_type` VARCHAR(30) NOT NULL DEFAULT 'alipay',
  `account_name` VARCHAR(80) NOT NULL,
  `account_no` VARCHAR(120) NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `review_note` TEXT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_developer_withdrawals_user` (`user_id`, `status`),
  KEY `idx_developer_withdrawals_status` (`status`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `market_acquisitions` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` VARCHAR(80) NOT NULL,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `user_id` BIGINT UNSIGNED DEFAULT NULL,
  `acquired_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_site_item` (`site_id`, `item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;



CREATE TABLE IF NOT EXISTS `market_item_images` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_market_item_images_item` (`item_id`, `sort_order`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `market_appeals` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` BIGINT UNSIGNED NOT NULL,
  `developer_user_id` BIGINT UNSIGNED NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `review_note` TEXT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_market_appeals_status` (`status`, `id`),
  KEY `idx_market_appeals_item` (`item_id`),
  KEY `idx_market_appeals_developer` (`developer_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(100) NOT NULL,
  `setting_value` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('user_site_unbind_enabled', '0'),
  ('email_verify_enabled', '0'),
  ('smtp_host', ''),
  ('smtp_port', '587'),
  ('smtp_secure', 'tls'),
  ('smtp_username', ''),
  ('smtp_password', ''),
  ('smtp_from_email', ''),
  ('smtp_from_name', 'Clay官方站'),
  ('alipay_enabled', '0'),
  ('alipay_gateway', 'https://openapi.alipay.com/gateway.do'),
  ('alipay_app_id', ''),
  ('alipay_private_key', ''),
  ('alipay_public_key', ''),
  ('developer_join_price', '99.00'),
  ('developer_share_ratio', '70'),
  ('developer_min_withdraw', '10.00')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);


CREATE TABLE IF NOT EXISTS `license_logs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` BIGINT UNSIGNED NOT NULL,
  `domain` VARCHAR(255) NOT NULL,
  `ip` VARCHAR(64) NOT NULL,
  `action` VARCHAR(32) NOT NULL,
  `detail` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_license_logs_site` (`site_id`),
  KEY `idx_license_logs_domain` (`domain`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


CREATE TABLE IF NOT EXISTS `site_limit_orders` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_no` VARCHAR(64) NOT NULL,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product` VARCHAR(20) NOT NULL DEFAULT 'claybbs',
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
  KEY `idx_site_limit_orders_user_status` (`user_id`, `status`),
  KEY `idx_site_limit_orders_user_product_status` (`user_id`, `product`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `site_limit_requests` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` BIGINT UNSIGNED NOT NULL,
  `product` VARCHAR(20) NOT NULL DEFAULT 'claybbs',
  `current_limit` INT UNSIGNED NOT NULL DEFAULT 0,
  `current_used` INT UNSIGNED NOT NULL DEFAULT 0,
  `requested_count` INT UNSIGNED NOT NULL DEFAULT 1,
  `reason` TEXT DEFAULT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `review_note` TEXT DEFAULT NULL,
  `reviewed_by` BIGINT UNSIGNED DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_site_limit_requests_user_status` (`user_id`, `status`),
  KEY `idx_site_limit_requests_user_product_status` (`user_id`, `product`, `status`),
  KEY `idx_site_limit_requests_status` (`status`, `id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
  ('site_limit_request_enabled', '0'),
  ('site_limit_request_max', '1'),
  ('auth_purchase_enabled', '0'),
  ('auth_purchase_price', '0.00'),
  ('auth_purchase_max', '10'),
  ('claybbs_site_limit_request_enabled', '0'),
  ('claybbs_site_limit_request_max', '1'),
  ('claybbs_auth_purchase_enabled', '0'),
  ('claybbs_auth_purchase_price', '0.00'),
  ('claybbs_auth_purchase_max', '10'),
  ('cutot_site_limit_request_enabled', '0'),
  ('cutot_site_limit_request_max', '1'),
  ('cutot_auth_purchase_enabled', '0'),
  ('cutot_auth_purchase_price', '0.00'),
  ('cutot_auth_purchase_max', '10')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- Synced from current database schema on 2026-05-12: previously missing from ClayBE install.sql.

CREATE TABLE IF NOT EXISTS `developer_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `apply_type` varchar(30) NOT NULL DEFAULT 'public',
  `reason` text,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `review_note` text,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_developer_applications_user` (`user_id`,`status`),
  KEY `idx_developer_applications_status` (`status`,`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
