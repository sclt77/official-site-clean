<?php
declare(strict_types=1);
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
$prefix = '/api';
$path = trim(substr($uri, strlen($prefix)), '/');
$_GET['path'] = $path !== '' ? $path : (string)($_GET['path'] ?? '');
require dirname(__DIR__) . '/api.php';
