<?php

namespace App\Services;

class PackageSigner
{
    private string $privateKeyPath;
    private string $publicKeyPath;

    public function __construct()
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->privateKeyPath = (string) ($config['sign_private_key'] ?? '');
        $this->publicKeyPath = (string) ($config['sign_public_key'] ?? '');
    }

    public function signFile(string $filePath): string
    {
        if (!file_exists($filePath)) {
            throw new \RuntimeException('待签名文件不存在');
        }
        if ($this->privateKeyPath === '' || !file_exists($this->privateKeyPath)) {
            throw new \RuntimeException('签名私钥不存在，请先配置 sign_private_key');
        }

        $privateKeyPem = file_get_contents($this->privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyPem ?: '');
        if (!$privateKey) {
            throw new \RuntimeException('签名私钥加载失败');
        }

        $data = file_get_contents($filePath);
        $signature = '';
        $ok = openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) {
            throw new \RuntimeException('文件签名失败');
        }

        return base64_encode($signature);
    }

    public function getPublicKey(): string
    {
        if ($this->publicKeyPath === '' || !file_exists($this->publicKeyPath)) {
            return '';
        }
        return (string) file_get_contents($this->publicKeyPath);
    }
}
