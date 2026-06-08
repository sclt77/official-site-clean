<?php
$pageTitle='日志';
$tabRaw = (string)($_GET['tab'] ?? '');
$allowedTabs = ['publish','downloads','license','keys'];
$tab = in_array($tabRaw, $allowedTabs, true) ? $tabRaw : 'publish';
$product = in_array(($product ?? ($_GET['product'] ?? 'claybbs')), ['claybbs','cutot'], true) ? ($product ?? ($_GET['product'] ?? 'claybbs')) : 'claybbs';
$productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
$siteQuery = !empty($siteId) ? '&site_id=' . urlencode((string)$siteId) : '';
$productQuery = '&product=' . urlencode($product);
require dirname(__DIR__) . '/layouts/main.php';
?>
<div class="card">
  <h2>日志中心</h2>
  <div style="color:var(--text-soft);margin-top:6px;">站点更新日志 / 包下载记录 / 授权校验日志 / Key 使用记录</div>
  <?php if (!empty($siteId)): ?>
    <div style="margin-top:10px;font-size:13px;color:#334155;">当前按站点筛选：<strong><?= htmlspecialchars($siteId) ?></strong>　<a href="/admin.php?path=logs&tab=<?= urlencode($tab) ?>&product=<?= urlencode($product) ?>">清除筛选</a></div>
  <?php endif; ?>
</div>

<div class="card log-tabs-card"><div class="log-tabs" role="tablist" aria-label="日志产品切换"><a class="log-tab <?= $product === 'claybbs' ? 'active' : '' ?>" href="/admin.php?path=logs&product=claybbs&tab=<?= urlencode($tab) ?><?= $siteQuery ?>">ClayBBS 日志</a><a class="log-tab <?= $product === 'cutot' ? 'active' : '' ?>" href="/admin.php?path=logs&product=cutot&tab=<?= urlencode($tab) ?><?= $siteQuery ?>">CUTOT 日志</a></div></div>

<div class="card log-tabs-card">
  <div class="log-tabs" role="tablist" aria-label="日志类型切换">
    <a class="log-tab <?= $tab === 'publish' ? 'active' : '' ?>" href="/admin.php?path=logs&tab=publish<?= $productQuery ?><?= $siteQuery ?>">站点更新日志 <em><?= count($publish ?? []) ?></em></a>
    <a class="log-tab <?= $tab === 'downloads' ? 'active' : '' ?>" href="/admin.php?path=logs&tab=downloads<?= $productQuery ?><?= $siteQuery ?>">包下载记录 <em><?= count($downloads ?? []) ?></em></a>
    <a class="log-tab <?= $tab === 'license' ? 'active' : '' ?>" href="/admin.php?path=logs&tab=license<?= $productQuery ?><?= $siteQuery ?>">授权校验日志 <em><?= count($licenseLogs ?? []) ?></em></a>
    <a class="log-tab <?= $tab === 'keys' ? 'active' : '' ?>" href="/admin.php?path=logs&tab=keys<?= $productQuery ?><?= $siteQuery ?>">Key 使用记录 <em><?= count($keys ?? []) ?></em></a>
  </div>
</div>

<?php if ($tab === 'publish'): ?>
<div class="card">
  <h3>更新结果概览</h3>
  <div style="display:flex;gap:10px;flex-wrap:wrap;">
    <?php foreach (($summary ?? []) as $s): ?><span class="badge <?= ($s['status'] ?? '')==='success'?'badge-ok':'badge-err' ?>"><?= htmlspecialchars((string)$s['status']) ?>：<?= (int)$s['cnt'] ?></span><?php endforeach; ?>
    <?php if (empty($summary)): ?><span style="color:var(--text-soft);">暂无上报</span><?php endif; ?>
  </div>
</div>

<div class="card">
  <h3>站点更新日志</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>时间</th><th>产品</th><th>站点</th><th>域名/邮箱</th><th>包ID</th><th>版本</th><th>类型</th><th>状态</th><th>耗时</th><th>事件</th><th>Key</th><th>健康检查</th><th>日志</th></tr></thead>
    <tbody>
    <?php foreach ($publish as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td><?= htmlspecialchars(strtoupper((string)($row['package_product'] ?? $row['site_product'] ?? $product))) ?></td>
        <td><?= htmlspecialchars($row['site_id']) ?></td>
        <td><?= htmlspecialchars((string)($row['site_domain'] ?? '')) ?><?= !empty($row['site_email']) ? ' / ' . htmlspecialchars((string)$row['site_email']) : '' ?></td>
        <td><?= (int)$row['package_id'] ?></td>
        <td><?= htmlspecialchars((string)($row['from_version'] ?? '')) ?> → <?= htmlspecialchars((string)($row['to_version'] ?? $row['version'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['kind'] ?? $row['type'] ?? '')) ?></td>
        <td><span class="badge <?= ($row['status'] ?? '')==='success'?'badge-ok':'badge-err' ?>"><?= htmlspecialchars($row['status']) ?></span></td>
        <td><?= !empty($row['duration_ms']) ? ((int)$row['duration_ms'] . 'ms') : '' ?></td>
        <td><?= htmlspecialchars((string)($row['event'] ?? '')) ?></td>
        <td style="font-size:12px;word-break:break-all;"><?= htmlspecialchars((string)($row['full_key'] ?? '')) ?></td>
        <td style="max-width:260px;white-space:pre-wrap;font-size:12px;"><?= htmlspecialchars((string)($row['health_json'] ?? '')) ?></td>
        <td style="max-width:300px;white-space:pre-wrap;"><?= htmlspecialchars((string)($row['log'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($publish)): ?><tr><td colspan="13" class="muted">暂无站点更新日志</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'downloads'): ?>
<div class="card">
  <h3>包下载记录</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>时间</th><th>产品</th><th>用户</th><th>站点</th><th>域名</th><th>类型</th><th>版本</th><th>文件</th></tr></thead>
    <tbody>
    <?php foreach ($downloads as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td><?= htmlspecialchars(strtoupper((string)($row['package_product'] ?? $row['site_product'] ?? $product))) ?></td>
        <td><?= htmlspecialchars((string)($row['user_email'] ?? $row['user_id'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['site_id'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['site_domain'] ?? '')) ?></td>
        <td><?= htmlspecialchars($row['kind']) ?></td>
        <td><?= htmlspecialchars((string)($row['version'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['filename'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($downloads)): ?><tr><td colspan="8" class="muted">暂无包下载记录</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'license'): ?>
<div class="card">
  <h3>授权校验日志</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>时间</th><th>产品</th><th>用户</th><th>站点</th><th>授权码</th><th>域名</th><th>动作</th><th>结果</th><th>IP</th><th>详情</th></tr></thead>
    <tbody>
    <?php foreach ($licenseLogs as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td><?= htmlspecialchars(strtoupper((string)($row['site_product'] ?? $product))) ?></td>
        <td><?= htmlspecialchars((string)($row['user_email'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['site_id'] ?? '')) ?></td>
        <td style="font-size:12px;word-break:break-all;"><?= htmlspecialchars((string)($row['license_key'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['domain'] ?? $row['site_domain'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['action'] ?? '')) ?></td>
        <td><?= strpos((string)($row['action'] ?? ''), 'deny') !== false ? '拒绝' : '通过' ?></td>
        <td><?= htmlspecialchars((string)($row['ip'] ?? '')) ?></td>
        <td style="max-width:240px;white-space:pre-wrap;"><?= htmlspecialchars((string)($row['detail'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($licenseLogs)): ?><tr><td colspan="10" class="muted">暂无授权校验日志</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<?php if ($tab === 'keys'): ?>
<div class="card">
  <h3>Key 使用记录</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>时间</th><th>产品</th><th>包版本</th><th>Key</th><th>是否已用</th><th>使用站点</th><th>使用时间</th></tr></thead>
    <tbody>
    <?php foreach ($keys as $row): ?>
      <tr>
        <td><?= htmlspecialchars($row['created_at']) ?></td>
        <td><?= htmlspecialchars(strtoupper((string)($row['product'] ?? $product))) ?></td>
        <td><?= htmlspecialchars((string)($row['version'] ?? '')) ?></td>
        <td style="font-size:12px;word-break:break-all;"><?= htmlspecialchars($row['full_key']) ?></td>
        <td><?= !empty($row['used']) ? '是' : '否' ?></td>
        <td><?= htmlspecialchars((string)($row['used_site'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($row['used_at'] ?? '')) ?></td>
      </tr>
    <?php endforeach; ?>
    <?php if (empty($keys)): ?><tr><td colspan="7" class="muted">暂无 Key 使用记录</td></tr><?php endif; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<style>
.log-tabs-card{padding:10px!important;}
.log-tabs{display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:2px;}
.log-tab{flex:0 0 auto;display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 14px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;font-weight:900;font-size:13px;white-space:nowrap;}
.log-tab.active,.log-tab:hover{background:#2563eb;border-color:#2563eb;color:#fff;}
.log-tab em{font-style:normal;display:inline-flex;min-width:20px;height:20px;padding:0 6px;border-radius:999px;align-items:center;justify-content:center;background:#e0f2fe;color:#0284c7;font-size:11px;font-weight:900;}
.log-tab.active em,.log-tab:hover em{background:rgba(255,255,255,.22);color:#fff;}
@media(max-width:760px){.log-tabs-card{padding:8px!important;}.log-tab{height:36px;padding:0 12px;font-size:12px;}}
</style>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
