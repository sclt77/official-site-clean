<?php

namespace App\Services;

class AlipayFacePayService
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function enabled(): bool
    {
        return !empty($this->settings['alipay_enabled'])
            && (string)$this->settings['alipay_enabled'] === '1'
            && trim((string)($this->settings['alipay_app_id'] ?? '')) !== ''
            && trim((string)($this->settings['alipay_private_key'] ?? '')) !== ''
            && trim((string)($this->settings['alipay_public_key'] ?? '')) !== '';
    }

    public function precreate(array $order, array $item): array
    {
        if (!$this->enabled()) {
            throw new \RuntimeException('支付宝当面付未配置，请联系管理员');
        }

        $bizContent = [
            'out_trade_no' => (string)$order['order_no'],
            'total_amount' => number_format((float)$order['amount'], 2, '.', ''),
            'subject' => $this->subject($item),
            'body' => 'ClayBBS Market Order ' . (string)$order['order_no'],
            'product_code' => 'FACE_TO_FACE_PAYMENT',
        ];

        $params = $this->baseParams('alipay.trade.precreate');
        $params['notify_url'] = $this->absoluteUrl('/api.php?path=market/pay-notify');
        $params['biz_content'] = json_encode($bizContent, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $params['sign'] = $this->rsaSign($this->signContent($params));

        $resp = $this->post($this->gateway(), $params);
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            throw new \RuntimeException('支付宝返回异常：' . mb_substr($resp, 0, 200));
        }

        if (!$this->verifyResponseBody($resp)) {
            throw new \RuntimeException('支付宝响应验签失败，请检查支付宝公钥是否填写为“支付宝公钥”而不是“应用公钥”');
        }

        $node = $data['alipay_trade_precreate_response'] ?? [];
        if (($node['code'] ?? '') !== '10000') {
            $msg = (string)($node['sub_msg'] ?? $node['msg'] ?? '预下单失败');
            throw new \RuntimeException('支付宝预下单失败：' . $msg);
        }

        return [
            'out_trade_no' => (string)($node['out_trade_no'] ?? $order['order_no']),
            'qr_code' => (string)($node['qr_code'] ?? ''),
            'raw' => $data,
        ];
    }


    public function query(string $orderNo): array
    {
        if (!$this->enabled()) {
            throw new \RuntimeException('支付宝当面付未配置，请联系管理员');
        }
        $params = $this->baseParams('alipay.trade.query');
        $params['biz_content'] = json_encode(['out_trade_no' => $orderNo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $params['sign'] = $this->rsaSign($this->signContent($params));
        $resp = $this->post($this->gateway(), $params);
        $data = json_decode($resp, true);
        if (!is_array($data)) {
            throw new \RuntimeException('支付宝查单返回异常：' . mb_substr($resp, 0, 200));
        }
        if (!$this->verifyResponseBody($resp)) {
            throw new \RuntimeException('支付宝查单响应验签失败，请检查支付宝公钥');
        }
        $node = $data['alipay_trade_query_response'] ?? [];
        return [
            'ok' => (($node['code'] ?? '') === '10000'),
            'trade_status' => (string)($node['trade_status'] ?? ''),
            'trade_no' => (string)($node['trade_no'] ?? ''),
            'total_amount' => (string)($node['total_amount'] ?? ''),
            'message' => (string)($node['sub_msg'] ?? $node['msg'] ?? ''),
            'raw' => $data,
        ];
    }

    public function verifyNotify(array $params): bool
    {
        $sign = (string)($params['sign'] ?? '');
        if ($sign === '') return false;
        unset($params['sign'], $params['sign_type']);
        return $this->rsaVerify($this->signContent($params), $sign);
    }

    private function baseParams(string $method): array
    {
        return [
            'app_id' => trim((string)$this->settings['alipay_app_id']),
            'method' => $method,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        ];
    }

    private function signContent(array $params): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value === '' || $value === null) continue;
            $pairs[] = $key . '=' . $value;
        }
        return implode('&', $pairs);
    }

    private function rsaSign(string $content): string
    {
        $privateKey = $this->formatPrivateKey((string)$this->settings['alipay_private_key']);
        $ok = openssl_sign($content, $sign, $privateKey, OPENSSL_ALGO_SHA256);
        if (!$ok) throw new \RuntimeException('支付宝请求签名失败，请检查应用私钥');
        return base64_encode($sign);
    }

    private function rsaVerify(string $content, string $sign): bool
    {
        $publicKey = $this->formatPublicKey((string)$this->settings['alipay_public_key']);
        $ok = openssl_verify($content, base64_decode($sign), $publicKey, OPENSSL_ALGO_SHA256);
        return $ok === 1;
    }

    private function verifyResponseBody(string $body): bool
    {
        $data = json_decode($body, true);
        if (!is_array($data) || empty($data['sign'])) return false;
        $responseKey = '';
        foreach ($data as $key => $_) {
            if ($key !== 'sign' && str_ends_with($key, '_response')) { $responseKey = $key; break; }
        }
        if ($responseKey === '') return false;
        $content = $this->extractResponseContent($body, $responseKey);
        if ($content === '') return false;
        return $this->rsaVerify($content, (string)$data['sign']);
    }

    private function extractResponseContent(string $body, string $responseKey): string
    {
        $needle = '"' . $responseKey . '":';
        $pos = strpos($body, $needle);
        if ($pos === false) return '';
        $start = strpos($body, '{', $pos + strlen($needle));
        if ($start === false) return '';
        $depth = 0;
        $inString = false;
        $escape = false;
        $len = strlen($body);
        for ($i = $start; $i < $len; $i++) {
            $ch = $body[$i];
            if ($inString) {
                if ($escape) { $escape = false; continue; }
                if ($ch === '\\') { $escape = true; continue; }
                if ($ch === '"') { $inString = false; }
                continue;
            }
            if ($ch === '"') { $inString = true; continue; }
            if ($ch === '{') { $depth++; continue; }
            if ($ch === '}') {
                $depth--;
                if ($depth === 0) return substr($body, $start, $i - $start + 1);
            }
        }
        return '';
    }


    private function subject(array $item): string
    {
        $name = trim((string)($item['name'] ?? ''));
        if ($name === '') $name = '应用授权';
        if (!mb_check_encoding($name, 'UTF-8')) {
            $name = mb_convert_encoding($name, 'UTF-8', 'GBK,GB2312,BIG5,UTF-8');
        }
        return mb_substr('ClayBBS-' . $name, 0, 80, 'UTF-8');
    }

    private function gateway(): string
    {
        $gateway = trim((string)($this->settings['alipay_gateway'] ?? ''));
        return $gateway !== '' ? $gateway : 'https://openapi.alipay.com/gateway.do';
    }

    private function post(string $url, array $params): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&', PHP_QUERY_RFC3986),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded; charset=utf-8'],
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $body = curl_exec($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code >= 400) {
            throw new \RuntimeException('请求支付宝失败：' . ($err ?: ('HTTP ' . $code)));
        }
        return (string)$body;
    }

    private function formatPrivateKey(string $key): string
    {
        $key = trim($key);
        if (str_contains($key, 'BEGIN')) return $key;
        $key = preg_replace('/\s+/', '', $key) ?? $key;
        return "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($key, 64, "\n") . "-----END RSA PRIVATE KEY-----\n";
    }

    private function formatPublicKey(string $key): string
    {
        $key = trim($key);
        if (str_contains($key, 'BEGIN')) return $key;
        $key = preg_replace('/\s+/', '', $key) ?? $key;
        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split($key, 64, "\n") . "-----END PUBLIC KEY-----\n";
    }

    private function absoluteUrl(string $path): string
    {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string)($_SERVER['HTTP_HOST'] ?? 'www.claybbs.com');
        return $scheme . '://' . $host . $path;
    }
}
