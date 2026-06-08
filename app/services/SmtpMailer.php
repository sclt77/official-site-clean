<?php

namespace App\Services;

class SmtpMailer
{
    private array $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function send(string $to, string $subject, string $html, string $text = ''): void
    {
        $host = trim((string)($this->settings['smtp_host'] ?? ''));
        $port = (int)($this->settings['smtp_port'] ?? 587);
        $secure = strtolower(trim((string)($this->settings['smtp_secure'] ?? 'tls')));
        $username = trim((string)($this->settings['smtp_username'] ?? ''));
        $password = (string)($this->settings['smtp_password'] ?? '');
        $fromEmail = trim((string)($this->settings['smtp_from_email'] ?? ''));
        if ($fromEmail === '') {
            $fromEmail = $username;
        }
        $fromName = trim((string)($this->settings['smtp_from_name'] ?? 'Clay官方站'));
        if ($host === '' || $port <= 0 || $fromEmail === '') {
            throw new \RuntimeException('SMTP 配置不完整');
        }
        if (!filter_var($fromEmail, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('发件邮箱或收件邮箱格式不正确');
        }
        if ($text === '') {
            $text = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)));
        }

        $target = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $fp = @stream_socket_client($target, $errno, $errstr, 15, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new \RuntimeException('SMTP 连接失败：' . $errstr);
        }
        stream_set_timeout($fp, 20);
        $this->expect($fp, [220]);
        $serverName = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $this->cmd($fp, 'EHLO ' . $serverName, [250]);
        if ($secure === 'tls') {
            $this->cmd($fp, 'STARTTLS', [220]);
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                throw new \RuntimeException('SMTP TLS 启动失败');
            }
            $this->cmd($fp, 'EHLO ' . $serverName, [250]);
        }
        if ($username !== '') {
            if ($password === '') {
                fclose($fp);
                throw new \RuntimeException('SMTP 密码/授权码未配置');
            }
            $this->cmd($fp, 'AUTH LOGIN', [334]);
            $this->cmd($fp, base64_encode($username), [334]);
            $this->cmd($fp, base64_encode($password), [235]);
        }
        $this->cmd($fp, 'MAIL FROM:<' . $fromEmail . '>', [250]);
        $this->cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        $this->cmd($fp, 'DATA', [354]);
        $boundary = 'b' . bin2hex(random_bytes(12));
        $headers = [];
        $headers[] = 'From: ' . $this->address($fromEmail, $fromName);
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . $this->encodeHeader($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $headers[] = 'Date: ' . date('r');
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . preg_replace('/[^a-z0-9.-]/i', '', $serverName) . '>';
        $body = implode("\r\n", $headers) . "\r\n\r\n"
            . '--' . $boundary . "\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $this->dotSafe($text) . "\r\n"
            . '--' . $boundary . "\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n" . $this->dotSafe($html) . "\r\n"
            . '--' . $boundary . "--\r\n.";
        $this->cmd($fp, $body, [250]);
        $this->cmd($fp, 'QUIT', [221]);
        fclose($fp);
    }

    private function address(string $email, string $name): string
    {
        return $this->encodeHeader($name) . ' <' . $email . '>';
    }

    private function encodeHeader(string $value): string
    {
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }

    private function dotSafe(string $body): string
    {
        return preg_replace('/^\./m', '..', str_replace("\n", "\r\n", str_replace("\r", '', $body))) ?? $body;
    }

    private function cmd($fp, string $command, array $expect): string
    {
        fwrite($fp, $command . "\r\n");
        return $this->expect($fp, $expect);
    }

    private function expect($fp, array $expect): string
    {
        $response = '';
        while (($line = fgets($fp, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, $expect, true)) {
            throw new \RuntimeException('SMTP 响应异常：' . trim($response));
        }
        return $response;
    }
}
