<?php

namespace App\Controllers;

class ClayGuardController
{
    public function installGuide(): void
    {
        $pageTitle = 'ClayGuard 安装教程';
        require dirname(__DIR__) . '/views/clayguard/install.php';
    }
}
