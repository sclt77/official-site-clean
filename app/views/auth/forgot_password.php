<?php $pageTitle='忘记密码'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class="page-shell">
<section class="auth-shell">
  <div class="auth-copy">
    <span>ClayBBS Account</span>
    <h1>重置密码</h1>
    <p>输入你的注册邮箱，我们会发送密码重置链接。</p>
  </div>
  <div class="auth-card">
    <h2>找回密码</h2>
    <?php if (!empty($error)): ?><div class="auth-alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if (!empty($success)): ?><div class="auth-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if (empty($success)): ?>
    <form method="post" action="/index.php?path=forgot-password" class="grid" data-no-ajax>
    <?= csrf_field() ?>
    <div><label>注册邮箱</label><input class="input" name="email" type="email" required placeholder="请输入邮箱地址"></div>
    <button class="btn" type="submit">发送重置链接</button>
    </form>
    <?php endif; ?>
    <div class="auth-foot"><a href="/index.php?path=login">返回登录</a></div>
  </div>
</section>
</div>
<style>
.front-shell{display:block}.front-sidebar{display:none}.sidebar-open .front-sidebar{display:block}.front-content{width:100%}.auth-shell{display:grid;grid-template-columns:minmax(0,1fr) 430px;gap:24px;align-items:center;min-height:calc(100vh - 160px);padding:28px;border:1px solid #dbeafe;border-radius:28px;background:radial-gradient(circle at 10% 20%,rgba(37,99,235,.16),transparent 30%),linear-gradient(135deg,#f8fbff,#fff 58%,#ecfeff)}.auth-copy span{color:#2563eb;font-size:12px;font-weight:950;letter-spacing:.12em;text-transform:uppercase}.auth-copy h1{font-size:clamp(38px,5vw,62px);line-height:1.05;letter-spacing:-.06em;margin:14px 0}.auth-copy p{color:#64748b;font-size:17px;line-height:1.8;max-width:560px}.auth-card{background:#fff;border:1px solid #e2e8f0;border-radius:24px;padding:24px;box-shadow:0 24px 70px rgba(15,23,42,.10)}.auth-card h2{font-size:26px;margin-bottom:14px}.auth-card form{display:grid;gap:12px}.auth-card label{display:block;margin-bottom:6px;color:#334155;font-size:13px;font-weight:850}.auth-card .btn{width:100%;margin-top:4px}.auth-alert{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:14px;padding:12px;margin-bottom:12px;line-height:1.6}.auth-success{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0;border-radius:14px;padding:12px;margin-bottom:12px;line-height:1.6}.auth-foot{text-align:center;margin-top:14px}.auth-foot a{color:#2563eb;text-decoration:none;font-weight:850}@media(max-width:860px){.auth-shell{grid-template-columns:1fr;padding:20px}.auth-card{padding:18px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
