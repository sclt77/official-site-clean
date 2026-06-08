<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\SettingModel;
use App\Services\RateLimiter;
use App\Services\SmtpMailer;

class AuthController
{
    public function register(): void
    {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $limiter = new RateLimiter(5, 300);
            $ip = $limiter->ip();
            $email = trim($_POST['email'] ?? '');
            if (!$limiter->check('auth_register_ip:' . $ip) || ($email !== '' && !$limiter->check('auth_register_email:' . strtolower($email)))) {
                http_response_code(429);
                $error = '请求过于频繁，请稍后再试';
            } else {
            $password = $_POST['password'] ?? '';
            $name = trim($_POST['name'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
                $error = '请输入有效邮箱，密码至少 6 位';
            } else {
                $userModel = new UserModel();
                if ($userModel->findByEmail($email)) {
                    $error = '该邮箱已注册';
                } else {
                    $settings = (new SettingModel())->getSiteConfig();
                    $emailVerifyEnabled = !empty($settings['email_verify_enabled']) && (string)$settings['email_verify_enabled'] === '1';
                    $uid = $userModel->create($email, $password, $name, 'user', !$emailVerifyEnabled);
                    if ($emailVerifyEnabled) {
                        $created = $userModel->find($uid);
                        if ($created) {
                            try {
                                $this->sendVerifyEmail($created, $settings);
                                $success = '注册成功，验证邮件已发送，请先完成邮箱验证后再登录。';
                            } catch (\Throwable $e) {
                                $success = '注册成功，但验证邮件发送失败：' . $e->getMessage() . '。请稍后使用“重发验证邮件”。';
                            }
                        } else {
                            $success = '注册成功，请稍后使用“重发验证邮件”完成邮箱验证。';
                        }
                    } else {
                        if (session_status() === PHP_SESSION_ACTIVE) {
                            session_regenerate_id(true);
                        }
                        $_SESSION['auth_user'] = ['id'=>$uid,'email'=>$email,'name'=>$name,'role'=>'user','status'=>'active','email_verified'=>1];
                        header('Location: /index.php'); exit;
                    }
                }
            }
            }
        }
        require dirname(__DIR__) . '/views/auth/register.php';
    }

    public function login(): void
    {
        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $limiter = new RateLimiter(5, 300);
            $ip = $limiter->ip();
            $email = trim($_POST['email'] ?? '');
            if (!$limiter->check('auth_login_ip:' . $ip) || ($email !== '' && !$limiter->check('auth_login_account:' . strtolower($email)))) {
                http_response_code(429);
                $error = '登录过于频繁，请稍后再试';
            } else {
            $password = $_POST['password'] ?? '';
            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);
            if (!$user || !password_verify($password, $user['password'])) {
                $error = '邮箱或密码错误';
            } elseif (($user['status'] ?? 'active') !== 'active') {
                $error = '当前账号已被禁用';
            } elseif ($this->emailVerifyEnabled() && empty($user['email_verified'])) {
                $error = '邮箱尚未验证，请先查收验证邮件。';
            } else {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }
                $_SESSION['auth_user'] = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'role' => $user['role'] ?? 'user',
                    'status' => $user['status'] ?? 'active',
                    'email_verified' => (int)($user['email_verified'] ?? 1),
                ];
                header('Location: /index.php'); exit;
            }
            }
        }
        require dirname(__DIR__) . '/views/auth/login.php';
    }

    public function verifyEmail(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $message = '验证链接无效或已过期。';
        if ($token !== '') {
            $userModel = new UserModel();
            $user = $userModel->findByVerifyToken($token);
            if ($user && empty($user['email_verified']) && !empty($user['email_verify_expires_at']) && strtotime((string)$user['email_verify_expires_at']) >= time()) {
                $userModel->markEmailVerified((int)$user['id']);
                $message = '邮箱验证成功，现在可以登录。';
            }
        }
        require dirname(__DIR__) . '/views/auth/verify_result.php';
    }

    public function resendVerify(): void
    {
        $error = '';
        $success = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $limiter = new RateLimiter(3, 300);
            $ip = $limiter->ip();
            $email = trim((string)($_POST['email'] ?? ''));
            if (!$limiter->check('auth_resend_ip:' . $ip) || ($email !== '' && !$limiter->check('auth_resend_email:' . strtolower($email)))) {
                http_response_code(429);
                $error = '请求过于频繁，请稍后再试';
            } else {
            $userModel = new UserModel();
            $user = $userModel->findByEmail($email);
            if (!$this->emailVerifyEnabled()) {
                $error = '当前未开启邮箱验证。';
            } elseif (!$user) {
                $error = '该邮箱尚未注册。';
            } elseif (!empty($user['email_verified'])) {
                $success = '该邮箱已经验证，可以直接登录。';
            } else {
                try {
                    $token = $userModel->refreshVerifyToken((int)$user['id']);
                    $user['email_verify_token'] = $token['token'];
                    $user['email_verify_expires_at'] = $token['expires_at'];
                    $this->sendVerifyEmail($user, (new SettingModel())->getSiteConfig());
                    $success = '验证邮件已重新发送。';
                } catch (\Throwable $e) {
                    $error = '发送失败：' . $e->getMessage();
                }
            }
            }
        }
        require dirname(__DIR__) . '/views/auth/resend_verify.php';
    }

    public function forgotPassword(): void
    {
        $error = '';
        $success = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $limiter = new RateLimiter(3, 300);
            $ip = $limiter->ip();
            $email = trim((string)($_POST['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = '请输入有效的邮箱地址';
            } elseif (!$limiter->check('auth_forgot_ip:' . $ip) || !$limiter->check('auth_forgot_email:' . strtolower($email))) {
                http_response_code(429);
                $error = '请求过于频繁，请稍后再试';
            } else {
                $userModel = new UserModel();
                $user = $userModel->findByEmail($email);
                if ($user) {
                    try {
                        $tokenData = $userModel->setPasswordResetToken($email);
                        if ($tokenData) {
                            $this->sendResetEmail($user, $tokenData, (new SettingModel())->getSiteConfig());
                        }
                        $success = '如果该邮箱已注册，密码重置链接已发送，请查收邮件。';
                    } catch (\Throwable $e) {
                        $success = '如果该邮箱已注册，密码重置链接已发送。';
                    }
                } else {
                    // 不提示邮箱不存在，防止枚举
                    $success = '如果该邮箱已注册，密码重置链接已发送，请查收邮件。';
                }
            }
        }
        require dirname(__DIR__) . '/views/auth/forgot_password.php';
    }

    public function resetPassword(): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $user = null;
        if ($token !== '') {
            $userModel = new UserModel();
            $user = $userModel->findByResetToken($token);
        }
        if (!$user) {
            echo '<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:system-ui,sans-serif;"><div style="text-align:center;padding:40px;background:#fef2f2;border:1px solid #fecaca;border-radius:16px;max-width:420px;"><h2 style="color:#991b1b;margin-bottom:12px">链接无效或已过期</h2><p style="color:#991b1b;margin-bottom:20px">请返回登录页面重新发起密码重置。</p><a href="/index.php?path=login" style="display:inline-block;background:#2563eb;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700">返回登录</a></div></div>';
            exit;
        }

        $error = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $password = $_POST['password'] ?? '';
            $password2 = $_POST['password2'] ?? '';
            if (strlen($password) < 6) {
                $error = '密码至少 6 位';
            } elseif ($password !== $password2) {
                $error = '两次密码不一致';
            } else {
                $userModel = new UserModel();
                $userModel->resetPassword((int)$user['id'], $password);
                echo '<div style="display:flex;align-items:center;justify-content:center;min-height:100vh;font-family:system-ui,sans-serif;"><div style="text-align:center;padding:40px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:16px;max-width:420px;"><h2 style="color:#166534;margin-bottom:12px">密码已重置</h2><p style="color:#166534;margin-bottom:20px">请使用新密码登录。</p><a href="/index.php?path=login" style="display:inline-block;background:#2563eb;color:#fff;padding:10px 24px;border-radius:8px;text-decoration:none;font-weight:700">立即登录</a></div></div>';
                exit;
            }
        }
        require dirname(__DIR__) . '/views/auth/reset_password.php';
    }

    private function sendResetEmail(array $user, array $tokenData, array $settings): void
    {
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? parse_url((string)($settings['site_url'] ?? ''), PHP_URL_HOST) ?: 'localhost');
        $url = $scheme . '://' . $host . '/index.php?path=reset-password&token=' . urlencode($tokenData['token']);
        $siteName = (string)($settings['site_name'] ?? 'Clay官方站');
        $subject = $siteName . ' 密码重置';
        $html = '<p>你好，' . htmlspecialchars((string)($user['name'] ?: $user['email']), ENT_QUOTES, 'UTF-8') . '：</p>'
            . '<p>你申请了密码重置，请点击下面的链接设置新密码，链接 30 分钟内有效：</p>'
            . '<p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p>如果不是你本人操作，请忽略这封邮件。</p>';
        (new SmtpMailer($settings))->send((string)$user['email'], $subject, $html);
    }

    private function emailVerifyEnabled(): bool
    {
        $settings = (new SettingModel())->getSiteConfig();
        return !empty($settings['email_verify_enabled']) && (string)$settings['email_verify_enabled'] === '1';
    }

    private function sendVerifyEmail(array $user, array $settings): void
    {
        $token = (string)($user['email_verify_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('缺少邮箱验证 token');
        }
        $scheme = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? parse_url((string)($settings['site_url'] ?? ''), PHP_URL_HOST) ?: 'localhost');
        $url = $scheme . '://' . $host . '/index.php?path=verify-email&token=' . urlencode($token);
        $siteName = (string)($settings['site_name'] ?? 'Clay官方站');
        $subject = $siteName . ' 邮箱验证';
        $html = '<p>你好，' . htmlspecialchars((string)($user['name'] ?: $user['email']), ENT_QUOTES, 'UTF-8') . '：</p>'
            . '<p>请点击下面的链接完成邮箱验证，链接 24 小时内有效：</p>'
            . '<p><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a></p>'
            . '<p>如果不是你本人操作，请忽略这封邮件。</p>';
        (new SmtpMailer($settings))->send((string)$user['email'], $subject, $html);
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 3600, $params['path'] ?: '/', $params['domain'] ?: '', (bool) $params['secure'], (bool) $params['httponly']);
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        header('Location: /index.php');
        exit;
    }
}
