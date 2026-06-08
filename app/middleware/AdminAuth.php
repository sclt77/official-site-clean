<?php

namespace App\Middleware;

class AdminAuth
{
    public static function check(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['auth_user'] ?? null;
        if ($user && !empty($user['id'])) {
            try {
                $freshUser = (new \App\Models\UserModel())->find((int)$user['id']);
                if ($freshUser) {
                    $_SESSION['auth_user'] = $freshUser;
                    $user = $freshUser;
                }
            } catch (\Throwable $e) {
                // 如果数据库临时异常，继续使用当前会话信息进行校验。
            }
        }
        $role = $user['role'] ?? 'user';
        $status = $user['status'] ?? 'active';
        if (!$user || $status !== 'active' || !in_array($role, ['admin', 'superadmin'], true)) {
            unset($_SESSION['auth_user']);
            header('Location: /index.php?path=login');
            exit;
        }
    }
}
