<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class SettingModel
{
    public function ensureTable(): void
    {
        Database::connection()->exec(
            "CREATE TABLE IF NOT EXISTS settings (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                setting_key VARCHAR(100) NOT NULL,
                setting_value TEXT DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY uk_setting_key (setting_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    public function all(): array
    {
        $this->ensureTable();
        $rows = Database::connection()->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
        }
        return $map;
    }

    public function get(string $key, string $default = ''): string
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare("SELECT setting_value FROM settings WHERE setting_key=:key LIMIT 1");
        $stmt->execute([':key' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string) $val : $default;
    }

    public function set(string $key, string $value): void
    {
        $this->ensureTable();
        $stmt = Database::connection()->prepare(
            "INSERT INTO settings (setting_key, setting_value)
             VALUES (:key, :value)
             ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), updated_at=NOW()"
        );
        $stmt->execute([':key' => $key, ':value' => $value]);
    }

    public function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set((string) $key, (string) $value);
        }
    }

    public function getSiteConfig(): array
    {
        $all = $this->all();
        return [
            'site_name' => $all['site_name'] ?? 'Clay官方站',
            'site_logo_text' => $all['site_logo_text'] ?? 'Clay官方站',
            'site_tagline' => $all['site_tagline'] ?? '提供官方更新、完整包分发与回滚能力。',
            'footer_text' => $all['footer_text'] ?? ('© ' . date('Y') . ' Clay官方站'),
            'user_site_unbind_enabled' => $all['user_site_unbind_enabled'] ?? '0',
            'site_limit_request_enabled' => $all['site_limit_request_enabled'] ?? '0',
            'site_limit_request_max' => $all['site_limit_request_max'] ?? '1',
            'auth_purchase_enabled' => $all['auth_purchase_enabled'] ?? '0',
            'auth_purchase_price' => $all['auth_purchase_price'] ?? '0.00',
            'auth_purchase_max' => $all['auth_purchase_max'] ?? '10',
            'claybbs_site_limit_request_enabled' => $all['claybbs_site_limit_request_enabled'] ?? ($all['site_limit_request_enabled'] ?? '0'),
            'claybbs_site_limit_request_max' => $all['claybbs_site_limit_request_max'] ?? ($all['site_limit_request_max'] ?? '1'),
            'claybbs_auth_purchase_enabled' => $all['claybbs_auth_purchase_enabled'] ?? ($all['auth_purchase_enabled'] ?? '0'),
            'claybbs_auth_purchase_price' => $all['claybbs_auth_purchase_price'] ?? ($all['auth_purchase_price'] ?? '0.00'),
            'claybbs_auth_purchase_max' => $all['claybbs_auth_purchase_max'] ?? ($all['auth_purchase_max'] ?? '10'),
            'cutot_site_limit_request_enabled' => $all['cutot_site_limit_request_enabled'] ?? '0',
            'cutot_site_limit_request_max' => $all['cutot_site_limit_request_max'] ?? '1',
            'cutot_auth_purchase_enabled' => $all['cutot_auth_purchase_enabled'] ?? '0',
            'cutot_auth_purchase_price' => $all['cutot_auth_purchase_price'] ?? '0.00',
            'cutot_auth_purchase_max' => $all['cutot_auth_purchase_max'] ?? '10',
            'email_verify_enabled' => $all['email_verify_enabled'] ?? '0',
            'smtp_host' => $all['smtp_host'] ?? '',
            'smtp_port' => $all['smtp_port'] ?? '587',
            'smtp_secure' => $all['smtp_secure'] ?? 'tls',
            'smtp_username' => $all['smtp_username'] ?? '',
            'smtp_password' => $all['smtp_password'] ?? '',
            'smtp_from_email' => $all['smtp_from_email'] ?? '',
            'smtp_from_name' => $all['smtp_from_name'] ?? ($all['site_name'] ?? 'Clay官方站'),
            'alipay_enabled' => $all['alipay_enabled'] ?? '0',
            'alipay_gateway' => $all['alipay_gateway'] ?? 'https://openapi.alipay.com/gateway.do',
            'alipay_app_id' => $all['alipay_app_id'] ?? '',
            'alipay_private_key' => $all['alipay_private_key'] ?? '',
            'alipay_public_key' => $all['alipay_public_key'] ?? '',
            'developer_join_price' => $all['developer_join_price'] ?? '99.00',
            'developer_share_ratio' => $all['developer_share_ratio'] ?? '70',
            'developer_min_withdraw' => $all['developer_min_withdraw'] ?? '10.00',
        ];
    }
}
