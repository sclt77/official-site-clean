<?php

namespace App\Controllers;

use App\Middleware\AdminAuth;

class AdminClayGuardController
{
    public function installGuide(): void
    {
        AdminAuth::check();
        $pageTitle = 'ClayGuard 安装教程';
        require dirname(__DIR__) . '/views/admin/clayguard_install.php';
    }
}
