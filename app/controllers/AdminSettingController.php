<?php

namespace App\Controllers;

use App\Middleware\AdminAuth;
use App\Models\SettingModel;
use App\Services\SmtpMailer;

class AdminSettingController
{
    public function index(): void
    {
        AdminAuth::check();
        $model = new SettingModel();
        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            try {
                $current = $model->getSiteConfig();
                $smtpPassword = (string)($_POST['smtp_password'] ?? '');
                if (!empty($_POST['smtp_password_clear'])) {
                    $smtpPassword = '';
                } elseif ($smtpPassword === '') {
                    $smtpPassword = (string)($current['smtp_password'] ?? '');
                }

                $alipayPrivateKey = (string)($_POST['alipay_private_key'] ?? '');
                if (!empty($_POST['alipay_private_key_clear'])) {
                    $alipayPrivateKey = '';
                } elseif ($alipayPrivateKey === '') {
                    $alipayPrivateKey = (string)($current['alipay_private_key'] ?? '');
                }
                $alipayPublicKey = (string)($_POST['alipay_public_key'] ?? '');
                if (!empty($_POST['alipay_public_key_clear'])) {
                    $alipayPublicKey = '';
                } elseif ($alipayPublicKey === '') {
                    $alipayPublicKey = (string)($current['alipay_public_key'] ?? '');
                }

                $model->saveMany([
                    'site_name' => trim((string) ($_POST['site_name'] ?? 'Clay官方站')),
                    'site_logo_text' => trim((string) ($_POST['site_logo_text'] ?? 'Clay官方站')),
                    'site_tagline' => trim((string) ($_POST['site_tagline'] ?? '提供官方更新、完整包分发与回滚能力。')),
                    'footer_text' => trim((string) ($_POST['footer_text'] ?? '')),
                    'user_site_unbind_enabled' => !empty($_POST['user_site_unbind_enabled']) ? '1' : '0',
                    'auth_purchase_enabled' => !empty($_POST['claybbs_auth_purchase_enabled']) ? '1' : '0',
                    'auth_purchase_price' => number_format(max(0, (float)($_POST['claybbs_auth_purchase_price'] ?? 0)), 2, '.', ''),
                    'auth_purchase_max' => (string)max(1, (int)($_POST['claybbs_auth_purchase_max'] ?? 10)),
                    'site_limit_request_enabled' => !empty($_POST['claybbs_site_limit_request_enabled']) ? '1' : '0',
                    'site_limit_request_max' => (string)max(1, (int)($_POST['claybbs_site_limit_request_max'] ?? 1)),
                    'claybbs_auth_purchase_enabled' => !empty($_POST['claybbs_auth_purchase_enabled']) ? '1' : '0',
                    'claybbs_auth_purchase_price' => number_format(max(0, (float)($_POST['claybbs_auth_purchase_price'] ?? 0)), 2, '.', ''),
                    'claybbs_auth_purchase_max' => (string)max(1, (int)($_POST['claybbs_auth_purchase_max'] ?? 10)),
                    'claybbs_site_limit_request_enabled' => !empty($_POST['claybbs_site_limit_request_enabled']) ? '1' : '0',
                    'claybbs_site_limit_request_max' => (string)max(1, (int)($_POST['claybbs_site_limit_request_max'] ?? 1)),
                    'cutot_auth_purchase_enabled' => !empty($_POST['cutot_auth_purchase_enabled']) ? '1' : '0',
                    'cutot_auth_purchase_price' => number_format(max(0, (float)($_POST['cutot_auth_purchase_price'] ?? 0)), 2, '.', ''),
                    'cutot_auth_purchase_max' => (string)max(1, (int)($_POST['cutot_auth_purchase_max'] ?? 10)),
                    'cutot_site_limit_request_enabled' => !empty($_POST['cutot_site_limit_request_enabled']) ? '1' : '0',
                    'cutot_site_limit_request_max' => (string)max(1, (int)($_POST['cutot_site_limit_request_max'] ?? 1)),
                    'email_verify_enabled' => !empty($_POST['email_verify_enabled']) ? '1' : '0',
                    'smtp_host' => trim((string)($_POST['smtp_host'] ?? '')),
                    'smtp_port' => (string)max(1, (int)($_POST['smtp_port'] ?? 587)),
                    'smtp_secure' => in_array(($_POST['smtp_secure'] ?? 'tls'), ['none','tls','ssl'], true) ? (string)$_POST['smtp_secure'] : 'tls',
                    'smtp_username' => trim((string)($_POST['smtp_username'] ?? '')),
                    'smtp_password' => $smtpPassword,
                    'smtp_from_email' => trim((string)($_POST['smtp_from_email'] ?? '')),
                    'smtp_from_name' => trim((string)($_POST['smtp_from_name'] ?? '')),
                    'alipay_enabled' => !empty($_POST['alipay_enabled']) ? '1' : '0',
                    'alipay_gateway' => trim((string)($_POST['alipay_gateway'] ?? 'https://openapi.alipay.com/gateway.do')),
                    'alipay_app_id' => trim((string)($_POST['alipay_app_id'] ?? '')),
                    'alipay_private_key' => $alipayPrivateKey,
                    'alipay_public_key' => $alipayPublicKey,
                    'developer_join_price' => number_format(max(0, (float)($_POST['developer_join_price'] ?? 99)), 2, '.', ''),
                    'developer_share_ratio' => (string)max(0, min(100, (float)($_POST['developer_share_ratio'] ?? 70))),
                    'developer_min_withdraw' => number_format(max(0, (float)($_POST['developer_min_withdraw'] ?? 10)), 2, '.', ''),
                ]);
                $success = '站点设置已保存';
            } catch (\Throwable $e) {
                $error = '保存失败：' . $e->getMessage();
            }
        }

        $settings = $model->getSiteConfig();
        require dirname(__DIR__) . '/views/admin/settings.php';
    }

    public function testSmtp(): void
    {
        AdminAuth::check();
        csrf_verify();

        $model = new SettingModel();
        $settings = $model->getSiteConfig();
        $error = '';
        $success = '';
        $testEmail = trim((string)($_POST['test_email'] ?? ''));

        try {
            if (!filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                throw new \RuntimeException('请填写有效的测试收件邮箱');
            }
            $siteName = (string)($settings['site_name'] ?? 'Clay官方站');
            $subject = $siteName . ' SMTP 测试邮件';
            $now = date('Y-m-d H:i:s');
            $html = '<p>这是一封来自 ' . htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') . ' 的 SMTP 测试邮件。</p>'
                . '<p>如果你收到这封邮件，说明后台 SMTP 配置可以正常发送。</p>'
                . '<p>发送时间：' . htmlspecialchars($now, ENT_QUOTES, 'UTF-8') . '</p>';
            $text = "这是一封来自 {$siteName} 的 SMTP 测试邮件。\n如果你收到这封邮件，说明后台 SMTP 配置可以正常发送。\n发送时间：{$now}";
            (new SmtpMailer($settings))->send($testEmail, $subject, $html, $text);
            $success = '测试邮件已发送到 ' . $testEmail;
            if (function_exists('ajax_ok')) {
                ajax_ok(['message' => $success]);
            }
        } catch (\Throwable $e) {
            $error = '测试发送失败：' . $e->getMessage();
            if (function_exists('is_ajax_request') && is_ajax_request()) {
                header('Content-Type: application/json');
                echo json_encode(['ok' => false, 'message' => $error], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }

        require dirname(__DIR__) . '/views/admin/settings.php';
    }
}
