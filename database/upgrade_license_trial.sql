-- ClayBBS 授权体验/永久/锁定状态升级，可重复执行
ALTER TABLE `sites` ADD COLUMN `license_type` VARCHAR(20) NOT NULL DEFAULT 'permanent' AFTER `license_status`;
ALTER TABLE `sites` ADD COLUMN `license_expires_at` DATETIME DEFAULT NULL AFTER `license_type`;
ALTER TABLE `sites` ADD KEY `idx_sites_license_type` (`license_type`);
UPDATE `sites` SET `license_type`='permanent' WHERE `license_type` IS NULL OR `license_type`='';
