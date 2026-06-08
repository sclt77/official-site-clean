<?php

namespace App\Controllers;

use App\Middleware\UserAuth;
use App\Models\UserModel;

class DevDocsController
{
    private array $examples = [
        'plugin-hello-notice' => [
            'file' => 'hello_notice_plugin.zip',
            'name' => 'hello_notice_plugin.zip',
        ],
        'theme-clay-light' => [
            'file' => 'clay_light_theme.zip',
            'name' => 'clay_light_theme.zip',
        ],
    ];

    public function index(): void
    {
        $user = $_SESSION['auth_user'] ?? null;
        $canDownloadExamples = $this->canDownloadExamples($user);
        require dirname(__DIR__) . '/views/devdocs/index.php';
    }

    public function download(): void
    {
        UserAuth::check();
        $user = $this->freshUser();
        if (!$this->canDownloadExamples($user)) {
            http_response_code(403);
            $pageTitle = '需要开发者权限';
            $messageTitle = '需要成为开发者后下载示例工程';
            $message = '插件/主题示例工程仅开放给公益开发者和普通开发者。你可以先申请公益开发者，或开通普通开发者权限。';
            $actionUrl = '/index.php?path=developer';
            $actionText = '前往开发者中心';
            require dirname(__DIR__) . '/views/devdocs/access_required.php';
            return;
        }
        $id = trim((string)($_GET['id'] ?? ''));
        $example = $this->examples[$id] ?? null;
        if (!$example) {
            http_response_code(404);
            exit('示例工程不存在');
        }
        $path = dirname(__DIR__, 2) . '/storage/devdocs/examples/' . basename((string)$example['file']);
        if (!is_file($path)) {
            http_response_code(404);
            exit('示例工程文件缺失');
        }
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: attachment; filename="' . basename((string)$example['name']) . '"');
        readfile($path);
        exit;
    }

    private function freshUser(): array
    {
        $id = (int)(($_SESSION['auth_user']['id'] ?? 0));
        $user = $id > 0 ? (new UserModel())->find($id) : null;
        if ($user) {
            $_SESSION['auth_user'] = $user;
            return $user;
        }
        return $_SESSION['auth_user'] ?? [];
    }

    private function canDownloadExamples(?array $user): bool
    {
        if (!$user) return false;
        if (($user['role'] ?? '') === 'admin') return true;
        if (($user['role'] ?? '') !== 'developer') return false;
        return in_array((string)($user['developer_level'] ?? 'none'), ['public','normal','professional','official'], true);
    }
}
