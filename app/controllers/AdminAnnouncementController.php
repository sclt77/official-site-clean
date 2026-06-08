<?php

namespace App\Controllers;

use App\Middleware\AdminAuth;
use App\Models\AnnouncementModel;

class AdminAnnouncementController
{
    public function index(): void
    {
        AdminAuth::check();
        $model = new AnnouncementModel();
        $error = '';
        $success = '';
        $editItem = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $action = trim((string) ($_POST['_action'] ?? 'save'));

            try {
                if ($action === 'delete') {
                    $id = (int) ($_POST['id'] ?? 0);
                    if ($id > 0) {
                        $model->delete($id);
                        $success = '公告已删除';
                    }
                } else {
                    $id = (int) ($_POST['id'] ?? 0);
                    $title = trim((string) ($_POST['title'] ?? ''));
                    $content = trim((string) ($_POST['content'] ?? ''));
                    $level = trim((string) ($_POST['level'] ?? 'info'));
                    $status = trim((string) ($_POST['status'] ?? 'active'));
                    $sortOrder = (int) ($_POST['sort_order'] ?? 0);

                    if ($title === '') {
                        throw new \RuntimeException('公告标题不能为空');
                    }
                    if (!in_array($level, ['info', 'warning', 'danger'], true)) {
                        $level = 'info';
                    }
                    if (!in_array($status, ['active', 'hidden'], true)) {
                        $status = 'active';
                    }

                    $payload = [
                        'title' => $title,
                        'content' => $content,
                        'level' => $level,
                        'status' => $status,
                        'sort_order' => $sortOrder,
                    ];

                    if ($id > 0) {
                        $model->update($id, $payload);
                        $success = '公告已更新';
                    } else {
                        $model->create($payload);
                        $success = '公告已添加';
                    }
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $editId = (int) ($_GET['edit'] ?? 0);
        if ($editId > 0) {
            $editItem = $model->find($editId);
        }

        $items = $model->all();
        require dirname(__DIR__) . '/views/admin/announcements.php';
    }
}
