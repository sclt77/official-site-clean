<?php $pageTitle='迁移初始化配置'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<style>.setup-card{max-width:760px;margin:0 auto}.setup-grid{display:grid;grid-template-columns:1fr 120px;gap:12px}.setup-grid .full{grid-column:1/-1}@media(max-width:640px){.setup-grid{grid-template-columns:1fr}}</style>
<div class="card setup-card"><h2>迁移初始化配置</h2><p class="muted" style="margin-top:8px;line-height:1.7">这是一次性入口。提交成功后会删除迁移标记并生成完成锁，之后不可再次访问。</p></div>
<?php if($error): ?><div class="card setup-card" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<div class="card setup-card"><h3 style="margin-bottom:12px;">迁移自检</h3><div class="grid"><?php foreach(($checks ?? []) as $c): ?><div style="display:flex;justify-content:space-between;gap:12px;border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:<?= !empty($c['ok']) ? '#f8fafc' : '#fef2f2' ?>;"><span><?= htmlspecialchars($c['label']) ?><br><small class="muted"><?= htmlspecialchars((string)$c['detail']) ?></small></span><b style="color:<?= !empty($c['ok']) ? '#16a34a' : '#dc2626' ?>"><?= !empty($c['ok']) ? '正常' : '异常' ?></b></div><?php endforeach; ?></div></div>
<?php if($success): ?><div class="card setup-card" style="background:#ecfdf5;color:#166534;border-color:#bbf7d0;"><?= htmlspecialchars($success) ?> <a href="/index.php">返回首页</a></div><?php else: ?>
<div class="card setup-card"><form method="post" action="/index.php?path=migration-setup" class="setup-grid">
  <?= csrf_field() ?>
  <div class="full"><label>迁移令牌<input class="input" name="token" required placeholder="迁移包 restore.md 中的 token"></label></div>
  <label>数据库 Host<input class="input" name="host" value="localhost" required></label>
  <label>端口<input class="input" name="port" type="number" value="3306" required></label>
  <label>数据库名<input class="input" name="database" required></label>
  <label>用户名<input class="input" name="username" required></label>
  <div class="full"><label>密码<input class="input" name="password" type="password"></label></div>
  <div class="full"><label>新站点 URL<input class="input" name="site_url" placeholder="https://www.claybbs.com"></label></div>
  <label class="full" style="display:flex;gap:8px;align-items:center;"><input type="checkbox" name="import_database" value="1" checked style="width:auto;"> 导入迁移包内 database.sql</label>
  <div class="full"><button class="btn" onclick="return confirm('确认写入新配置并锁定迁移入口？')">完成迁移配置</button></div>
</form></div>
<?php endif; ?>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
