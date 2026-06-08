<?php
declare(strict_types=1);
if (isset($_GET['route']) && !isset($_GET['path'])) {
    $map = [
        'check-update' => 'api/check-update',
        'download' => 'api/download',
        'report' => 'api/report',
        'public-key' => 'api/public-key',
        'license.activate' => 'api/license/activate',
        'license.verify' => 'api/license/verify',
        'clayguard.issue' => 'api/clayguard/issue',
    ];
    $_GET['path'] = $map[$_GET['route']] ?? ('api/' . str_replace('.', '/', trim((string)$_GET['route'], '/')));
} else {
    $_GET['path'] = 'api/' . ltrim((string)($_GET['path'] ?? ''), '/');
}
$_SERVER['SCRIPT_NAME'] = '/api.php';
require __DIR__ . '/index.php';
