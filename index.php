<?php

declare(strict_types=1);

require_once __DIR__ . '/app/core/bootstrap.php';

// INSTALL_REDIRECT_GUARD
$__installPath = __DIR__ . '/storage/installed.lock';
$__requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$__scriptName = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
$__isInstallRequest = in_array($__requestPath, ['/install', '/install.php'], true) || $__scriptName === 'install.php' || in_array(($_GET['path'] ?? ''), ['install', 'install.php'], true);
$__isMigrationSetupRequest = in_array($__requestPath, ['/migration-setup'], true) || (($_GET['path'] ?? '') === 'migration-setup');
$__migrationPending = __DIR__ . '/migration/pending.lock';
$__migrationCompleted = __DIR__ . '/migration/completed.lock';
if (is_file($__migrationPending) && !is_file($__migrationCompleted) && !$__isMigrationSetupRequest) {
    header('Location: /index.php?path=migration-setup');
    exit;
}
$__isApiRequest = str_starts_with($__requestPath, '/api') || $__scriptName === 'api.php';
if (!file_exists($__installPath) && !$__isInstallRequest && !$__isMigrationSetupRequest && !$__isApiRequest) {
    header('Location: /install.php');
    exit;
}


use App\Core\Router;

$router = new Router();
require_once __DIR__ . '/routes/web.php';
// NO_REWRITE_COMPAT: allow /index.php?path=login, /install.php, /admin.php?path=users, /api.php?path=...
$__uri = $_SERVER['REQUEST_URI'] ?? '/';
$__script = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
$__query = [];
$__queryString = parse_url($__uri, PHP_URL_QUERY);
if (is_string($__queryString) && $__queryString !== '') {
    parse_str($__queryString, $__query);
}
$__pathParam = $_GET['path'] ?? ($__query['path'] ?? null);
if (is_string($__pathParam) && $__pathParam !== '') {
    $__path = '/' . ltrim($__pathParam, '/');
    $__qs = array_merge($__query, $_GET);
    unset($__qs['path']);
    $__uri = $__path . ($__qs ? '?' . http_build_query($__qs) : '');
} elseif ($__script === 'index.php' && in_array($__requestPath, ['/', '/index.php'], true)) {
    $__uri = '/' . ($__query ? '?' . http_build_query($__query) : '');
} elseif ($__script === 'install.php') {
    $__uri = '/install' . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
} elseif ($__script === 'admin.php') {
    $__adminPath = $_GET['path'] ?? ($__query['path'] ?? '');
    $__adminPath = is_string($__adminPath) && $__adminPath !== '' ? '/' . ltrim($__adminPath, '/') : '';
    $__qs = array_merge($__query, $_GET);
    unset($__qs['path']);
    $__uri = '/admin' . $__adminPath . ($__qs ? '?' . http_build_query($__qs) : '');
} elseif ($__script === 'api.php') {
    $__apiPath = $_GET['path'] ?? ($__query['path'] ?? '');
    $__apiPath = is_string($__apiPath) && $__apiPath !== '' ? '/' . ltrim($__apiPath, '/') : '';
    $__qs = array_merge($__query, $_GET);
    unset($__qs['path']);
    $__uri = '/api' . $__apiPath . ($__qs ? '?' . http_build_query($__qs) : '');
}
$router->dispatch($__uri, $_SERVER['REQUEST_METHOD'] ?? 'GET');
