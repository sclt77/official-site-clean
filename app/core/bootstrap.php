<?php

declare(strict_types=1);

spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR;
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);

    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
    if (file_exists($file)) {
        require $file;
        return;
    }

    // Linux 环境大小写敏感：回退到大小写无关路径解析
    $parts = explode('\\', $relativeClass);
    $resolved = $baseDir;

    foreach ($parts as $index => $part) {
        $isLast = $index === count($parts) - 1;
        $targetName = $isLast ? $part . '.php' : $part;

        if (!is_dir($resolved)) {
            return;
        }

        $matched = null;
        foreach (scandir($resolved) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (strcasecmp($entry, $targetName) === 0) {
                $matched = $entry;
                break;
            }
        }

        if ($matched === null) {
            return;
        }

        $resolved .= $matched;
        if (!$isLast) {
            $resolved .= DIRECTORY_SEPARATOR;
        }
    }

    if (is_file($resolved)) {
        require $resolved;
    }
});

if (!function_exists('app_is_https')) {
    function app_is_https(): bool
    {
        if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
            return true;
        }
        if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
            return true;
        }
        $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
        $forwardedSsl = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
        return $forwardedProto === 'https' || $forwardedSsl === 'on';
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $secure = app_is_https();
    $params = session_get_cookie_params();
    session_name('GFGXSESSID');
    session_set_cookie_params([
        'lifetime' => 86400 * 30,
        'path' => $params['path'] ?: '/',
        'domain' => $params['domain'] ?: '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.gc_maxlifetime', (string) (86400 * 30));
    session_start();
}

require_once dirname(__DIR__) . '/helpers/csrf.php';
require_once dirname(__DIR__) . '/helpers/ajax.php';
