-- GFGX 旧库授权升级脚本
-- 作用：为已有 sites 表补充授权字段，并创建授权日志表

ALTER TABLE `sites`
  ADD COLUMN `license_key` VARCHAR(64) DEFAULT NULL,
  ADD COLUMN `license_status` VARCHAR(20) NOT NULL DEFAULT 'active',
  ADD COLUMN `bound_at` DATETIME DEFAULT NULL;

CREATE UNIQUE INDEX `uk_sites_license_key` ON `sites`(`license_key`);

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

-- 为旧站点补齐授权码（可重复执行前请先确认未生成过）
UPDATE `sites`
SET `license_key` = CONCAT('LIC-', UPPER(SUBSTRING(MD5(CONCAT(`id`, `site_id`, `domain`, NOW())), 1, 12))),
    `license_status` = 'active',
    `bound_at` = COALESCE(`bound_at`, `created_at`, NOW())
WHERE (`license_key` IS NULL OR `license_key` = '');
