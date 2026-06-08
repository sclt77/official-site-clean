<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\InstallController;
use App\Controllers\MeController;
use App\Controllers\DeveloperController;
use App\Controllers\AdminController;
use App\Controllers\AdminFullpackController;
use App\Controllers\AdminPublishController;
use App\Controllers\AdminLogController;
use App\Controllers\AdminUserController;
use App\Controllers\AdminSiteController;
use App\Controllers\AdminAnnouncementController;
use App\Controllers\AdminSettingController;
use App\Controllers\AdminMarketController;
use App\Controllers\AdminOrderController;
use App\Controllers\AdminMigrationController;
use App\Controllers\MigrationSetupController;
use App\Controllers\ApiController;
use App\Controllers\DownloadController;
use App\Controllers\DevDocsController;
use App\Controllers\ClayGuardController;
use App\Controllers\AdminClayGuardController;

$router->get('/', [HomeController::class, 'index']);
$router->get('/history', [HomeController::class, 'versionTree']);
$router->get('/history/full', [HomeController::class, 'fullHistory']);
$router->get('/history/diff', [HomeController::class, 'diffHistory']);
$router->get('/devdocs', [DevDocsController::class, 'index']);
$router->get('/devdocs/download', [DevDocsController::class, 'download']);
$router->get('/clayguard-install', [ClayGuardController::class, 'installGuide']);
$router->get('/install', [InstallController::class, 'index']);
$router->post('/install', [InstallController::class, 'index']);
$router->get('/migration-setup', [MigrationSetupController::class, 'index']);
$router->post('/migration-setup', [MigrationSetupController::class, 'index']);

$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'login']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->get('/verify-email', [AuthController::class, 'verifyEmail']);
$router->get('/resend-verify', [AuthController::class, 'resendVerify']);
$router->post('/resend-verify', [AuthController::class, 'resendVerify']);
$router->get('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->post('/forgot-password', [AuthController::class, 'forgotPassword']);
$router->get('/reset-password', [AuthController::class, 'resetPassword']);
$router->post('/reset-password', [AuthController::class, 'resetPassword']);

$router->get('/me', [MeController::class, 'index']);
$router->post('/me/edit-profile', [MeController::class, 'editProfile']);
$router->get('/me/edit-profile', [MeController::class, 'editProfile']);
$router->get('/user', [MeController::class, 'profile']);
$router->get('/me/sites', [MeController::class, 'sites']);
$router->post('/me/sites', [MeController::class, 'sites']);
$router->get('/me/site-limit-pay', [MeController::class, 'siteLimitPay']);
$router->get('/me/clayguard-license', [MeController::class, 'clayguardLicense']);
$router->get('/me/downloads', [MeController::class, 'downloads']);
$router->get('/me/keys', [MeController::class, 'keys']);
$router->get('/me/orders', [MeController::class, 'orders']);
$router->get('/market', [MeController::class, 'market']);
$router->post('/me/purchases', [MeController::class, 'purchases']);
$router->get('/me/purchases', [MeController::class, 'purchases']);
$router->post('/market/acquire', [MeController::class, 'marketAcquire']);
$router->get('/market/pay', [MeController::class, 'marketPay']);
$router->get('/market/detail', [MeController::class, 'marketDetail']);
$router->get('/developer', [DeveloperController::class, 'index']);
$router->post('/developer', [DeveloperController::class, 'index']);
$router->get('/developer/join-pay', [DeveloperController::class, 'joinPay']);
$router->get('/developer/history', [DeveloperController::class, 'history']);
$router->get('/market/plugins', [MeController::class, 'plugins']);
$router->get('/market/themes', [MeController::class, 'themes']);

$router->get('/download/full', [DownloadController::class, 'full']);

$router->get('/admin', [AdminController::class, 'index']);
$router->get('/admin/fullpacks', [AdminFullpackController::class, 'index']);
$router->post('/admin/fullpacks', [AdminFullpackController::class, 'index']);
$router->get('/admin/fullpacks/view', [AdminFullpackController::class, 'view']);
$router->post('/admin/fullpacks/replace', [AdminFullpackController::class, 'replace']);
$router->post('/admin/fullpacks/delete', [AdminFullpackController::class, 'delete']);
$router->get('/admin/publish', [AdminPublishController::class, 'index']);
$router->post('/admin/publish', [AdminPublishController::class, 'index']);
$router->post('/admin/publish/toggle', [AdminPublishController::class, 'toggle']);
$router->post('/admin/publish/delete', [AdminPublishController::class, 'delete']);
$router->get('/admin/logs', [AdminLogController::class, 'index']);
$router->get('/admin/users', [AdminUserController::class, 'index']);
$router->post('/admin/users/toggle', [AdminUserController::class, 'toggle']);
$router->get('/admin/sites', [AdminSiteController::class, 'index']);
$router->post('/admin/sites/update', [AdminSiteController::class, 'update']);
$router->post('/admin/sites/limit-request', [AdminSiteController::class, 'reviewLimitRequest']);
$router->get('/admin/announcements', [AdminAnnouncementController::class, 'index']);
$router->post('/admin/announcements', [AdminAnnouncementController::class, 'index']);
$router->get('/admin/settings', [AdminSettingController::class, 'index']);
$router->post('/admin/settings', [AdminSettingController::class, 'index']);
$router->post('/admin/settings/test-smtp', [AdminSettingController::class, 'testSmtp']);
$router->get('/admin/market', [AdminMarketController::class, 'index']);
$router->post('/admin/market', [AdminMarketController::class, 'index']);
$router->get('/admin/orders', [AdminOrderController::class, 'index']);
$router->post('/admin/orders', [AdminOrderController::class, 'index']);
$router->get('/admin/migration', [AdminMigrationController::class, 'index']);
$router->post('/admin/migration', [AdminMigrationController::class, 'index']);
$router->get('/admin/migration/download', [AdminMigrationController::class, 'download']);
$router->get('/admin/clayguard-install', [AdminClayGuardController::class, 'installGuide']);

// API
$router->get('/api/public-key', [ApiController::class, 'publicKey']);
$router->post('/api/check-update', [ApiController::class, 'checkUpdate']);
$router->post('/api/download', [ApiController::class, 'download']);
$router->post('/api/report', [ApiController::class, 'report']);

// License
$router->post('/api/license/activate', [ApiController::class, 'licenseActivate']);
$router->post('/api/license/verify', [ApiController::class, 'licenseVerify']);
$router->post('/api/clayguard/issue', [ApiController::class, 'clayguardIssue']);

// CUTOT official license/update API
$router->post('/api/cutot/license/verify', [ApiController::class, 'cutotLicenseVerify']);
$router->post('/api/cutot/update/check', [ApiController::class, 'cutotUpdateCheck']);
$router->post('/api/cutot/update/download', [ApiController::class, 'cutotUpdateDownload']);
$router->post('/api/cutot/update/report', [ApiController::class, 'cutotUpdateReport']);

$router->post('/api/market/list', [ApiController::class, 'marketList']);
$router->post('/api/market/acquire', [ApiController::class, 'marketAcquire']);
$router->post('/api/market/download', [ApiController::class, 'marketDownload']);
$router->post('/api/market/key-download', [ApiController::class, 'marketKeyDownload']);
$router->get('/api/market/pay-notify', [ApiController::class, 'marketPayNotify']);
$router->post('/api/market/pay-notify', [ApiController::class, 'marketPayNotify']);
