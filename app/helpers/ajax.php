<?php

declare(strict_types=1);

function is_ajax_request(): bool
{
    return strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
}

function ajax_ok(array $extra = []): void
{
    if (!is_ajax_request()) {
        return;
    }
    header('Content-Type: application/json');
    echo json_encode(array_merge(['ok' => true], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function redirect_or_ajax(string $url, array $extra = []): void
{
    if (is_ajax_request()) {
        ajax_ok($extra + ['redirect' => $url]);
    }
    header('Location: ' . $url);
    exit;
}
