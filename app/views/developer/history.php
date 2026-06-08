<?php $pageTitle='应用版本历史'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<?php
$app = $app ?? [];
$versions = $versions ?? [];
function dev_version_status_label(string $status): string { return ['pending'=>'待审核','published'=>'已通过','rejected'=>'已驳回'][$status] ?? $status; }
function dev_version_status_class(string $status): string { return in_array($status, ['pending','published','rejected'], true) ? $status : 'pending'; }
?>
<div class="card history-head">
  <div>
    <a class="back-link" href="/index.php?path=developer">返回开发者中心</a>
    <h2><?= htmlspecialchars((string)($app['name'] ?? '应用')) ?> · 版本历史</h2>
    <div class="muted">slug: <?= htmlspecialchars((string)($app['slug'] ?? '')) ?> · 当前版本：<?= htmlspecialchars((string)($app['version'] ?? '')) ?> · 共 <?= count($versions) ?> 条版本记录</div>
  </div>
  <span class="type-pill"><?= ($app['type'] ?? '') === 'theme' ? '主题模板' : '插件' ?></span>
</div>

<div class="card">
  <?php if ($versions): ?>
    <div class="version-timeline">
      <?php foreach ($versions as $index => $v): ?>
        <article class="version-item">
          <div class="version-dot"></div>
          <div class="version-card">
            <div class="version-card-head">
              <div>
                <strong>v<?= htmlspecialchars((string)$v['version']) ?></strong>
                <?php if ($index === 0): ?><span class="latest-tag">最新</span><?php endif; ?>
              </div>
              <em class="status-pill <?= htmlspecialchars(dev_version_status_class((string)$v['status'])) ?>"><?= htmlspecialchars(dev_version_status_label((string)$v['status'])) ?></em>
            </div>
            <div class="version-time"><?= htmlspecialchars((string)$v['created_at']) ?></div>
            <div class="version-block"><b>更新说明</b><p><?= nl2br(htmlspecialchars((string)($v['changelog'] ?: '暂无更新说明'))) ?></p></div>
            <?php if (!empty($v['review_note'])): ?><div class="version-block"><b>审核说明</b><p><?= nl2br(htmlspecialchars((string)$v['review_note'])) ?></p></div><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-history">暂无版本记录。返回开发者中心提交第一个版本包。</div>
  <?php endif; ?>
</div>

<style>
.history-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}.history-head h2{font-size:28px;margin:8px 0}.back-link{display:inline-flex;color:#2563eb;text-decoration:none;font-weight:800;font-size:13px}.back-link:hover{text-decoration:underline}.type-pill{display:inline-flex;border-radius:999px;padding:6px 10px;background:#eff6ff;color:#2563eb;font-size:12px;font-weight:900}.version-timeline{position:relative;display:grid;gap:14px}.version-timeline:before{content:"";position:absolute;left:10px;top:10px;bottom:10px;width:2px;background:#e2e8f0}.version-item{position:relative;display:grid;grid-template-columns:24px minmax(0,1fr);gap:12px}.version-dot{width:12px;height:12px;border-radius:999px;background:#2563eb;margin:8px 0 0 4px;box-shadow:0 0 0 4px #eff6ff;z-index:1}.version-card{border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:14px}.version-card-head{display:flex;justify-content:space-between;gap:12px;align-items:center}.version-card-head strong{font-size:18px}.latest-tag{display:inline-flex;margin-left:8px;border-radius:999px;background:#dcfce7;color:#166534;padding:3px 7px;font-size:12px;font-weight:900}.status-pill{font-style:normal;border-radius:999px;padding:5px 8px;font-size:12px;font-weight:900;background:#eff6ff;color:#1d4ed8}.status-pill.published{background:#dcfce7;color:#166534}.status-pill.rejected{background:#fee2e2;color:#991b1b}.version-time{margin-top:6px;color:#94a3b8;font-size:12px}.version-block{margin-top:12px}.version-block b{display:block;color:#64748b;font-size:12px;margin-bottom:5px}.version-block p{margin:0;color:#334155;line-height:1.7;overflow-wrap:anywhere}.empty-history{text-align:center;color:#94a3b8;padding:38px 0}@media(max-width:640px){.history-head h2{font-size:23px}.version-card-head{align-items:flex-start}.version-item{grid-template-columns:20px minmax(0,1fr);gap:8px}.version-timeline:before{left:8px}.version-dot{margin-left:2px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
