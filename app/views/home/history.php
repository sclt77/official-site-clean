<?php $pageTitle = $historyTitle . ' - ' . ($site['site_name'] ?? 'Clay官方站'); require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class="page-shell">
<section class="card release-hero">
  <div><span class="badge">Release History</span><h1><?= htmlspecialchars($historyTitle) ?></h1><p><?= $historyType === 'full' ? '完整包的发布历史与版本演进。历史完整包仅展示，下载永远只开放最新版。' : '更新包的发布历史与版本演进。更新包仅供论坛后台热更新使用，不开放前台下载。' ?></p></div>
  <div class="release-hero-stat"><span>发布记录</span><strong><?= count($packages ?? []) ?></strong><small><?= $historyType === 'full' ? '完整包' : '更新包' ?></small></div>
</section>

<div class="card">
  <div class="history-head-row"><div><h2>版本树</h2><div class="muted">按发布时间倒序展示，最新版本排在最上方。</div></div><a class="btn btn-light" href="/index.php">返回首页</a></div>
  <?php if (!empty($packages)): ?>
    <div class="timeline-tree">
      <?php $latestFullId = (int)(($historyType === 'full' && !empty($packages)) ? ($packages[0]['id'] ?? 0) : 0); ?>
      <?php foreach ($packages as $index => $p): $isLatestFull = $historyType === 'full' && (int)($p['id'] ?? 0) === $latestFullId; ?>
        <article class="timeline-item">
          <div class="timeline-dot"></div>
          <div class="timeline-card">
            <div class="timeline-card-head"><div><strong>v<?= htmlspecialchars((string)$p['version']) ?></strong><span>发布时间：<?= htmlspecialchars((string)($p['created_at'] ?? $p['updated_at'] ?? '')) ?></span></div><em><?= $historyType === 'full' ? '完整包' : '更新包' ?></em></div>
            <div class="meta-grid">
              <div><span class="meta-label">文件名</span><div class="break-all"><?= htmlspecialchars((string)($historyType === 'full' ? ($p['full_filename'] ?: $p['filename']) : $p['filename'])) ?></div></div>
              <?php if ($historyType === 'diff'): ?><div><span class="meta-label">回滚包</span><div class="break-all"><?= htmlspecialchars((string)($p['rollback_filename'] ?? '-')) ?></div></div><?php endif; ?>
              <div><span class="meta-label">状态</span><div><?= htmlspecialchars((string)$p['status']) ?></div></div>
              <div><span class="meta-label">分支</span><div><?= htmlspecialchars((string)($p['branch'] ?? 'main')) ?></div></div>
            </div>
            <div class="notes-section"><span class="meta-label">版本说明</span><div class="notes-box break-all"><?= nl2br(htmlspecialchars((string)($p['notes'] ?? '暂无说明'))) ?></div></div>
            <?php if ($historyType === 'full'): ?><div class="history-actions"><?php if ($isLatestFull): ?><a class="btn" href="/index.php?path=download/full&id=<?= (int)$p['id'] ?>">下载最新版完整包</a><?php else: ?><div class="muted history-note">历史完整包不可下载，请下载最新版完整包。</div><?php endif; ?></div><?php else: ?><div class="muted history-note">更新包仅用于论坛后台热更新，不提供前台下载。</div><?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?><div class="muted empty-history">暂时还没有可展示的发布记录。</div><?php endif; ?>
</div>

<style>.release-hero{display:grid;grid-template-columns:minmax(0,1fr) 240px;gap:20px;align-items:stretch;padding:30px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.release-hero h1{margin:14px 0 10px;font-size:40px;line-height:1.12;letter-spacing:-.045em}.release-hero p{color:#64748b;line-height:1.8}.release-hero-stat{display:grid;align-content:center;gap:8px;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.78);box-shadow:0 18px 50px rgba(37,99,235,.10);padding:18px}.release-hero-stat span,.release-hero-stat small{color:#64748b;font-size:13px;font-weight:800}.release-hero-stat strong{font-size:34px;letter-spacing:-.04em}.history-head-row{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap;margin-bottom:16px}.history-head-row h2{margin:0 0 6px}.timeline-tree{position:relative;padding-left:18px}.timeline-tree:before{content:"";position:absolute;left:8px;top:6px;bottom:6px;width:2px;background:#dbeafe}.timeline-item{position:relative;padding-left:26px;margin-bottom:18px}.timeline-item:last-child{margin-bottom:0}.timeline-dot{position:absolute;left:0;top:18px;width:18px;height:18px;border-radius:999px;background:#2563eb;border:4px solid #dbeafe}.timeline-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:16px;padding:16px}.timeline-card-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;flex-wrap:wrap}.timeline-card-head strong{display:block;font-size:22px;line-height:1.1}.timeline-card-head span{display:block;margin-top:6px;color:#64748b;font-size:13px}.timeline-card-head em{font-style:normal;display:inline-flex;align-items:center;padding:6px 10px;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:900}.meta-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}.meta-grid>div{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px}.meta-label{display:block;font-size:12px;color:#64748b;margin-bottom:6px;font-weight:800}.notes-section{margin-top:14px}.notes-box{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:12px;min-height:68px;line-height:1.7}.history-actions,.history-note{margin-top:14px}.empty-history{text-align:center;padding:34px 0}@media(max-width:768px){.release-hero{grid-template-columns:1fr;padding:20px}.release-hero h1{font-size:30px}.meta-grid{grid-template-columns:1fr}.timeline-tree{padding-left:12px}.timeline-item{padding-left:22px}}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
