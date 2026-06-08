<?php
$pageTitle = '后台控制台';
require dirname(__DIR__) . '/layouts/main.php';
$stats = $stats ?? [];
$siteCount = (int)($stats['sites'] ?? $stats['site_count'] ?? 0);
$orderCount = (int)($stats['orders'] ?? $stats['order_count'] ?? 0);
$userCount = (int)($stats['users'] ?? $stats['user_count'] ?? 0);
$versionCount = (int)($stats['packages'] ?? $stats['versions'] ?? $stats['version_count'] ?? 0);
?>
<section class="admin-hero-apple">
  <p class="eyebrow">Clay Admin</p>
  <h1>清爽后台。</h1>
  <p>授权、订单、更新与设置，按业务归类。</p>
  <div class="apple-actions">
    <a class="apple-btn primary" href="/admin.php?path=sites&product=claybbs&tab=requests">审核授权申请<?= !empty($pendingLimitRequestCount) ? '（' . (int)$pendingLimitRequestCount . '）' : '' ?></a>
    <a class="apple-btn secondary" href="/admin.php?path=fullpacks">版本更新</a>
  </div>
</section>

<div class="admin-lanes">
  <section class="admin-lane">
    <h2>授权商业</h2>
    <a href="/admin.php?path=sites&product=claybbs&tab=requests"><span>ClayBBS 授权申请</span><strong><?= (int)($pendingLimitRequestCountClay ?? 0) ?></strong></a>
    <a href="/admin.php?path=sites&product=cutot&tab=requests"><span>CUTOT 授权申请</span><strong><?= (int)($pendingLimitRequestCountCutot ?? 0) ?></strong></a>
    <a href="/admin.php?path=orders"><span>订单管理</span><strong><?= $orderCount ?></strong></a>
    <a href="/admin.php?path=users"><span>用户权限</span><strong><?= $userCount ?></strong></a>
  </section>
  <section class="admin-lane">
    <h2>分发生态</h2>
    <a href="/admin.php?path=fullpacks&product=claybbs"><span>ClayBBS 版本更新</span><strong><?= (int)($stats['claybbs_packages'] ?? 0) ?></strong></a>
    <a href="/admin.php?path=fullpacks&product=cutot"><span>CUTOT 版本更新</span><strong><?= (int)($stats['cutot_packages'] ?? 0) ?></strong></a>
    <a href="/admin.php?path=market"><span>应用市场</span><strong>进入</strong></a>
    <a href="/admin.php?path=publish"><span>发布版本</span><strong>新建</strong></a>
  </section>
  <section class="admin-lane">
    <h2>系统设置</h2>
    <a href="/admin.php?path=settings"><span>站点配置</span><strong>设置</strong></a>
    <a href="/admin.php?path=migration"><span>迁移备份</span><strong>检查</strong></a>
    <a href="/admin.php?path=logs"><span>日志审计</span><strong>查看</strong></a>
  </section>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
