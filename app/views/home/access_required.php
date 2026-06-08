<?php $pageTitle = ($messageTitle ?? '需要授权') . ' - ' . ($site['site_name'] ?? 'Clay官方站'); require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card simple-state-card">
  <span class="badge">Access Required</span><h1><?= htmlspecialchars($messageTitle ?? '需要授权') ?></h1>
  <p class="muted"><?= htmlspecialchars($message ?? '当前内容需要满足授权条件后才可查看。') ?></p>
  <div class="state-actions"><a class="btn" href="<?= htmlspecialchars($actionUrl ?? '/index.php?path=me/sites') ?>"><?= htmlspecialchars($actionText ?? '前往处理') ?></a><a class="btn btn-light" href="/index.php">返回首页</a></div>
</section>
<style>.simple-state-card{max-width:720px;margin:0 auto;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 45%,#ecfeff 100%);border-color:#bfdbfe}.simple-state-card h1{margin:14px 0 12px;font-size:34px;letter-spacing:-.04em}.simple-state-card p{line-height:1.8}.state-actions{margin-top:18px;display:flex;gap:10px;flex-wrap:wrap}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
