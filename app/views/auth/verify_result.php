<?php $pageTitle='邮箱验证'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card simple-state-card">
  <span class="badge">Email Verify</span><h1>邮箱验证</h1>
  <p class="muted"><?= htmlspecialchars($message ?? '') ?></p>
  <div class="state-actions"><a class="btn" href="/index.php?path=login">去登录</a><a class="btn btn-light" href="/index.php">返回首页</a></div>
</section>
<style>.simple-state-card{max-width:560px;margin:36px auto;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 45%,#ecfeff 100%);border-color:#bfdbfe}.simple-state-card h1{margin:14px 0 12px;font-size:32px;letter-spacing:-.04em}.simple-state-card p{line-height:1.8}.state-actions{margin-top:18px;display:flex;gap:10px;flex-wrap:wrap}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
