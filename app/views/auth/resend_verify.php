<?php $pageTitle='重发验证邮件'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card simple-state-card">
  <span class="badge">Email Verify</span><h1>重发验证邮件</h1>
  <?php if (!empty($error)): ?><div class="state-msg err"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (!empty($success)): ?><div class="state-msg ok"><?= htmlspecialchars($success) ?></div><?php endif; ?>
  <form method="post" action="/index.php?path=resend-verify" class="grid" data-no-ajax>
    <?= csrf_field() ?>
    <div><label>注册邮箱</label><input class="input" name="email" required></div>
    <button class="btn" type="submit">重新发送</button>
    <a class="btn btn-light" href="/index.php?path=login">返回登录</a>
  </form>
</section>
<style>.simple-state-card{max-width:520px;margin:36px auto;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 45%,#ecfeff 100%);border-color:#bfdbfe}.simple-state-card h1{margin:14px 0 18px;font-size:32px;letter-spacing:-.04em}.simple-state-card form{gap:12px}.state-msg{border-radius:12px;padding:10px 12px;margin-bottom:12px}.state-msg.err{background:#fee2e2;color:#b91c1c}.state-msg.ok{background:#dcfce7;color:#166534}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
