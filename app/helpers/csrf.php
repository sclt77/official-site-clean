<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf_token'];
}

function csrf_field(): string
{
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

function csrf_verify(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    $submitted = $_POST['_csrf_token'] ?? '';
    $expected = $_SESSION['_csrf_token'] ?? '';
    if ($expected === '' || !hash_equals($expected, $submitted)) {
        http_response_code(403);
        exit('403 CSRF verification failed');
    }
    if (strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) !== 'xmlhttprequest') {
        unset($_SESSION['_csrf_token']);
    }
}
