<?php

namespace App\Middleware;

class UserAuth
{
    public static function check(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $user = $_SESSION['auth_user'] ?? null;
        if (!$user || (($user['status'] ?? 'active') !== 'active')) {
            unset($_SESSION['auth_user']);
            header('Location: /index.php?path=login');
            exit;
        }
    }
}
