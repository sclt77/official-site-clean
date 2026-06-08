<?php

namespace App\Services;

class KeyManager
{
    public function ensureKeyPair(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $privatePath = (string) ($config['sign_private_key'] ?? '');
        $publicPath = (string) ($config['sign_public_key'] ?? '');

        if ($privatePath === '' || $publicPath === '') {
            throw new \RuntimeException('签名密钥路径未配置');
        }

        $dir = dirname($privatePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (!file_exists($privatePath) || !file_exists($publicPath)) {
            $opensslConfig = $this->resolveOpenSslConfig();
            $args = [
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'private_key_bits' => 2048,
            ];
            if ($opensslConfig !== null) {
                $args['config'] = $opensslConfig;
            }

            $res = openssl_pkey_new($args);
            if (!$res) {
                throw new \RuntimeException('生成 RSA 密钥对失败：' . $this->collectOpenSslErrors());
            }

            $privateKey = '';
            $exportOk = openssl_pkey_export($res, $privateKey, null, $opensslConfig !== null ? ['config' => $opensslConfig] : []);
            $details = openssl_pkey_get_details($res);
            $publicKey = (string) ($details['key'] ?? '');
            if (!$exportOk || $privateKey === '' || $publicKey === '') {
                throw new \RuntimeException('导出密钥失败：' . $this->collectOpenSslErrors());
            }

            file_put_contents($privatePath, $privateKey);
            file_put_contents($publicPath, $publicKey);
        }

        return [
            'private' => $privatePath,
            'public' => $publicPath,
        ];
    }

    private function resolveOpenSslConfig(): ?string
    {
        $candidates = [
            getenv('OPENSSL_CONF') ?: '',
            dirname(__DIR__, 2) . '/storage/keys/openssl.cnf',
            'C:/BtSoft/php/82/extras/ssl/openssl.cnf',
            'C:/BtSoft/php/74/extras/ssl/openssl.cnf',
            'C:/phpstudy_pro/Extensions/php/php8.2.9nts/extras/ssl/openssl.cnf',
        ];

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path !== '' && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function collectOpenSslErrors(): string
    {
        $errors = [];
        while ($msg = openssl_error_string()) {
            $errors[] = $msg;
        }
        return $errors ? implode(' | ', $errors) : 'unknown error';
    }
}
