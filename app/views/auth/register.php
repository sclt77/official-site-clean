<?php $pageTitle='注册'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="auth-shell">
  <div class="auth-copy">
    <span>ClayBBS Account</span>
    <h1>创建 ClayBBS 账号</h1>
    <p>绑定授权站点，获取官方版本与生态应用。</p>
    <ul><li>官方签名版本下载</li><li>授权域名与密钥管理</li><li>插件主题市场与开发者平台</li></ul>
  </div>
  <div class="auth-card">
    <h2>开始使用</h2>
    <?php if (!empty($error)): ?><div class="auth-alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post" action="/index.php?path=register" class="grid" data-no-ajax>
    <?= csrf_field() ?>
    <div><label>邮箱</label><input class="input" name="email" required></div>
    <div><label>昵称</label><input class="input" name="name"></div>
    <div><label>密码</label><input class="input" name="password" type="password" required></div>
    <button class="btn" type="submit">注册</button>
  </form>
    <div class="auth-foot"><a href="/index.php?path=login">已有账号？去登录</a></div>
  </div>
</section>
<style>
.front-shell{display:block}.front-sidebar{display:none}.sidebar-open .front-sidebar{display:block}.front-content{width:100%}.auth-shell{display:grid;grid-template-columns:minmax(0,1fr) 430px;gap:24px;align-items:center;min-height:calc(100vh - 160px);padding:28px;border:1px solid #dbeafe;border-radius:28px;background:radial-gradient(circle at 10% 20%,rgba(37,99,235,.16),transparent 30%),linear-gradient(135deg,#f8fbff,#fff 58%,#ecfeff)}.auth-copy span{color:#2563eb;font-size:12px;font-weight:950;letter-spacing:.12em;text-transform:uppercase}.auth-copy h1{font-size:clamp(38px,5vw,62px);line-height:1.05;letter-spacing:-.06em;margin:14px 0}.auth-copy p{color:#64748b;font-size:17px;line-height:1.8;max-width:560px}.auth-copy ul{display:flex;gap:9px;flex-wrap:wrap;list-style:none;margin-top:22px;padding:0}.auth-copy li{border:1px solid #e2e8f0;background:#fff;border-radius:999px;padding:7px 10px;color:#64748b;font-size:12px;font-weight:850}.auth-card{background:#fff;border:1px solid #e2e8f0;border-radius:24px;padding:24px;box-shadow:0 24px 70px rgba(15,23,42,.10)}.auth-card h2{font-size:26px;margin-bottom:14px}.auth-card form{display:grid;gap:12px}.auth-card label{display:block;margin-bottom:6px;color:#334155;font-size:13px;font-weight:850}.auth-card .btn{width:100%;margin-top:4px}.auth-alert{background:#fef2f2;color:#991b1b;border:1px solid #fecaca;border-radius:14px;padding:12px;margin-bottom:12px;line-height:1.6}.auth-alert a{color:#991b1b;font-weight:900}.auth-foot{text-align:center;margin-top:14px}.auth-foot a{color:#2563eb;text-decoration:none;font-weight:850}@media(max-width:860px){.auth-shell{grid-template-columns:1fr;padding:20px}.auth-card{padding:18px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
