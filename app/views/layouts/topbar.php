<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$user = $_SESSION['auth_user'] ?? null;
if ($user && !empty($user['id'])) {
    try {
        $freshUser = (new \App\Models\UserModel())->find((int)$user['id']);
        if ($freshUser && (($freshUser['status'] ?? 'active') === 'active')) {
            $_SESSION['auth_user'] = $freshUser;
            $user = $freshUser;
        }
    } catch (\Throwable $e) {
        // 顶栏不能因为数据库临时异常影响整站渲染，保留当前会话信息。
    }
}
$isAdminUser = $user && in_array((string)($user['role'] ?? ''), ['admin', 'superadmin'], true);
$siteCfg = $siteCfg ?? ['site_name' => 'Clay官方站', 'site_logo_text' => 'Clay'];
$brand = trim((string)($siteCfg['site_logo_text'] ?? $siteCfg['site_name'] ?? 'Clay')) ?: 'Clay';
?>
<header class="topbar-v3">
  <div class="topbar-inner">
    <button class="hamburger-v3" type="button" aria-label="打开菜单" aria-expanded="false"><span></span></button>
    <a class="brand-v3" href="<?= $__isAdminShell ? '/admin.php' : '/index.php' ?>">
      <span class="brand-mark" aria-hidden="true"></span>
      <span class="brand-text"><?= htmlspecialchars($brand) ?></span>
      <span class="brand-sub"><?= $__isAdminShell ? 'Admin' : 'Official' ?></span>
    </a>
    <nav class="nav-v3" aria-label="主导航">
      <?php if ($__isAdminShell): ?>
        <a href="/admin.php">控制台</a>
        <a href="/admin.php?path=sites">授权</a>
        <a href="/admin.php?path=market">市场</a>
        <a href="/admin.php?path=settings">设置</a>
      <?php else: ?>
        <a href="/index.php">首页</a>
        <a href="/index.php?path=market">市场</a>
        <a href="/index.php?path=history">版本树</a>
        <a href="/index.php?path=devdocs">文档</a>
        <a href="/index.php?path=me/sites">授权</a>
      <?php endif; ?>
    </nav>
    <?php if ($user): ?>
      <div class="user-v3">
        <button class="user-trigger-v3" type="button" aria-label="用户菜单">
          <img class="user-avatar" src="<?= htmlspecialchars($user['avatar'] ?? '/assets/avatar.svg') ?>" alt="">
          <span class="user-name"><?= htmlspecialchars($user['username'] ?? $user['email'] ?? '用户') ?></span>
        </button>
        <div class="user-menu-v3">
          <a href="/index.php?path=me">用户中心</a>
          <a href="/index.php?path=me/sites">我的授权</a>
          <?php if ($isAdminUser): ?><a href="/admin.php">后台管理</a><?php endif; ?>
          <a href="/index.php?path=logout">退出登录</a>
        </div>
      </div>
    <?php else: ?>
      <div class="auth-links">
        <a href="/index.php?path=login">登录</a>
        <a class="auth-primary" href="/index.php?path=register">注册</a>
      </div>
    <?php endif; ?>
  </div>
</header>
<script>
(function(){
  var body=document.body;
  var btn=document.querySelector('.hamburger-v3');
  var backdrop=document.querySelector('[data-close-drawer]');
  function close(){body.classList.remove('sidebar-open'); if(btn)btn.setAttribute('aria-expanded','false');}
  function toggle(){var open=!body.classList.contains('sidebar-open'); body.classList.toggle('sidebar-open',open); if(btn)btn.setAttribute('aria-expanded',open?'true':'false');}
  if(btn)btn.addEventListener('click',toggle);
  if(backdrop)backdrop.addEventListener('click',close);
  document.addEventListener('keydown',function(e){if(e.key==='Escape')close();});
  document.querySelectorAll('.drawer-v3 a').forEach(function(a){a.addEventListener('click',close);});
  document.querySelectorAll('.user-v3').forEach(function(wrap){
    var trigger=wrap.querySelector('.user-trigger-v3');
    if(trigger)trigger.addEventListener('click',function(e){e.stopPropagation();wrap.classList.toggle('open');});
  });
  document.addEventListener('click',function(e){document.querySelectorAll('.user-v3.open').forEach(function(wrap){if(!wrap.contains(e.target))wrap.classList.remove('open');});});
})();
</script>
