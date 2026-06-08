<?php $pageTitle='用户管理'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<div class="card admin-users-head">
  <h2>用户管理</h2>
  <div class="muted">查看用户、设置角色、开发者认证、ClayBBS/CUTOT 绑定数量、启用/禁用。新注册用户默认是非开发者，也默认不能绑定域名，需要管理员分配权限。</div>
</div>

<div class="card admin-user-search">
  <form method="get" action="/admin.php" class="admin-user-search-form">
    <input type="hidden" name="path" value="users">
    <div>
      <label>搜索用户</label>
      <input class="input" name="q" value="<?= htmlspecialchars((string)($q ?? '')) ?>" placeholder="输入 ID / 邮箱 / 名称">
    </div>
    <button class="btn" type="submit">搜索</button>
    <?php if (!empty($q)): ?><a class="btn btn-light" href="/admin.php?path=users">清空</a><?php endif; ?>
  </form>
</div>

<div class="card admin-users-card">
  <div class="admin-users-summary">
    <h3>用户列表</h3>
    <span><?= count($users) ?> 条<?= !empty($q) ? '搜索结果' : '最近用户' ?></span>
  </div>

  <div class="table-wrap admin-users-table">
  <table class="table">
    <thead><tr><th>ID</th><th>用户</th><th>角色</th><th>开发者认证</th><th>邮箱验证</th><th>绑定数量</th><th>状态</th><th>注册时间</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <tr>
        <td><?= (int)$u['id'] ?></td>
        <td class="user-cell"><b><?= htmlspecialchars((string)($u['name'] ?: '未设置')) ?></b><span><?= htmlspecialchars($u['email']) ?></span></td>
        <td><?= htmlspecialchars($u['role']) ?></td>
        <td><?= htmlspecialchars(['none'=>'非开发者','public'=>'公益开发者','normal'=>'普通开发者','professional'=>'专业开发者','official'=>'官方开发者'][$u['developer_level'] ?? 'none'] ?? '非开发者') ?></td>
        <td><span class="status-pill <?= !empty($u['email_verified'])?'ok':'off' ?>"><?= !empty($u['email_verified'])?'已验证':'未验证' ?></span></td>
        <td><div class="limit-pair"><span>ClayBBS <?= (int)($u['claybbs_site_count'] ?? 0) ?> / <?= (int)($u['claybbs_site_limit'] ?? $u['site_limit'] ?? 0) ?></span><span>CUTOT <?= (int)($u['cutot_site_count'] ?? 0) ?> / <?= (int)($u['cutot_site_limit'] ?? 0) ?></span></div></td>
        <td><span class="status-pill <?= $u['status']==='active'?'ok':'off' ?>"><?= htmlspecialchars($u['status']) ?></span></td>
        <td><?= htmlspecialchars($u['created_at']) ?></td>
        <td><?= admin_user_form($u) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <div class="admin-users-mobile">
    <?php foreach ($users as $u): ?>
      <article class="admin-user-item">
        <div class="admin-user-main">
          <div class="admin-user-avatar"><?= htmlspecialchars(strtoupper(substr((string)($u['name'] ?: $u['email'] ?: 'U'), 0, 1))) ?></div>
          <div>
            <strong><?= htmlspecialchars((string)($u['name'] ?: '未设置名称')) ?></strong>
            <span><?= htmlspecialchars($u['email']) ?></span>
            <em>ID <?= (int)$u['id'] ?> · <?= htmlspecialchars($u['created_at']) ?></em>
          </div>
        </div>
        <div class="admin-user-meta">
          <span>角色：<?= htmlspecialchars($u['role']) ?></span>
          <span>认证：<?= htmlspecialchars(['none'=>'非开发者','public'=>'公益开发者','normal'=>'普通开发者','professional'=>'专业开发者','official'=>'官方开发者'][$u['developer_level'] ?? 'none'] ?? '非开发者') ?></span>
          <span>邮箱：<?= !empty($u['email_verified'])?'已验证':'未验证' ?></span>
          <span>ClayBBS：<?= (int)($u['claybbs_site_count'] ?? 0) ?> / <?= (int)($u['claybbs_site_limit'] ?? $u['site_limit'] ?? 0) ?></span>
          <span>CUTOT：<?= (int)($u['cutot_site_count'] ?? 0) ?> / <?= (int)($u['cutot_site_limit'] ?? 0) ?></span>
          <span>状态：<?= htmlspecialchars($u['status']) ?></span>
        </div>
        <details class="admin-user-actions">
          <summary>修改用户</summary>
          <?= admin_user_form($u) ?>
        </details>
      </article>
    <?php endforeach; ?>
    <?php if (empty($users)): ?><div class="muted" style="text-align:center;padding:28px 0;">没有找到用户</div><?php endif; ?>
  </div>
</div>

<?php
function admin_user_form(array $u): string
{
    ob_start();
    ?>
    <form method="post" action="/admin.php?path=users/toggle" class="admin-user-form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
      <label>角色
        <select class="select" name="role">
          <option value="user" <?= $u['role']==='user'?'selected':'' ?>>user</option>
          <option value="developer" <?= $u['role']==='developer'?'selected':'' ?>>developer</option>
          <option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>admin</option>
        </select>
      </label>
      <label>开发者认证
        <select class="select" name="developer_level">
          <option value="none" <?= ($u['developer_level'] ?? 'none')==='none'?'selected':'' ?>>非开发者</option>
          <option value="public" <?= ($u['developer_level'] ?? 'none')==='public'?'selected':'' ?>>公益开发者</option>
          <option value="normal" <?= ($u['developer_level'] ?? 'none')==='normal'?'selected':'' ?>>普通开发者</option>
          <option value="professional" <?= ($u['developer_level'] ?? 'none')==='professional'?'selected':'' ?>>专业开发者</option>
          <option value="official" <?= ($u['developer_level'] ?? 'none')==='official'?'selected':'' ?>>官方开发者</option>
        </select>
      </label>
      <label>ClayBBS 绑定数
        <input class="input" type="number" name="claybbs_site_limit" min="0" max="999" value="<?= (int)($u['claybbs_site_limit'] ?? $u['site_limit'] ?? 0) ?>">
      </label>
      <label>CUTOT 绑定数
        <input class="input" type="number" name="cutot_site_limit" min="0" max="999" value="<?= (int)($u['cutot_site_limit'] ?? 0) ?>">
      </label>
      <label>邮箱验证
        <select class="select" name="email_verified">
          <option value="1" <?= !empty($u['email_verified'])?'selected':'' ?>>已验证</option>
          <option value="0" <?= empty($u['email_verified'])?'selected':'' ?>>未验证</option>
        </select>
      </label>
      <label>状态
        <select class="select" name="status">
          <option value="active" <?= $u['status']==='active'?'selected':'' ?>>启用</option>
          <option value="disabled" <?= $u['status']==='disabled'?'selected':'' ?>>禁用</option>
        </select>
      </label>
      <button class="btn btn-light" type="submit">保存</button>
    </form>
    <?php
    return trim(ob_get_clean());
}
?>

<style>
.admin-users-head h2{font-size:32px;margin-bottom:8px}.admin-user-search-form{display:grid;grid-template-columns:minmax(0,1fr) auto auto;gap:12px;align-items:end}.admin-user-search-form label,.admin-user-form label{display:grid;gap:6px;color:var(--text-soft);font-size:12px;font-weight:800}.admin-users-summary{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px}.admin-users-summary h3{margin:0}.admin-users-summary span{color:#94a3b8;font-size:13px}.user-cell b{display:block;color:#0f172a}.user-cell span{display:block;color:#64748b;font-size:12px;margin-top:3px}.status-pill{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:12px;font-weight:900;background:#f1f5f9;color:#64748b}.status-pill.ok{background:#dcfce7;color:#166534}.status-pill.off{background:#fee2e2;color:#991b1b}.limit-pair{display:grid;gap:4px;font-size:12px;font-weight:800;color:#475569}.limit-pair span{white-space:nowrap}.admin-user-form{display:grid;grid-template-columns:110px 130px 96px 96px 92px 92px auto;gap:8px;align-items:end}.admin-user-form .select,.admin-user-form .input{height:38px;padding:8px 10px}.admin-users-mobile{display:none}
@media(max-width:900px){.admin-users-head h2{font-size:28px}.admin-user-search-form{grid-template-columns:1fr}.admin-users-table{display:none}.admin-users-mobile{display:grid;gap:12px}.admin-users-card{padding:12px}.admin-user-item{border:1px solid var(--line);border-radius:16px;padding:14px;background:#fff;box-shadow:0 8px 22px rgba(15,23,42,.04)}.admin-user-main{display:flex;gap:12px;align-items:center}.admin-user-avatar{width:46px;height:46px;border-radius:16px;background:linear-gradient(135deg,#2563eb,#06b6d4);display:grid;place-items:center;color:#fff;font-weight:900;font-size:18px;flex:0 0 auto}.admin-user-main strong{display:block;font-size:16px}.admin-user-main span{display:block;color:#64748b;font-size:13px;word-break:break-all;margin-top:2px}.admin-user-main em{display:block;font-style:normal;color:#94a3b8;font-size:12px;margin-top:4px}.admin-user-meta{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:12px}.admin-user-meta span{background:#f8fafc;border-radius:10px;padding:8px;color:#334155;font-size:12px;font-weight:800}.admin-user-actions{margin-top:12px}.admin-user-actions summary{cursor:pointer;list-style:none;border-radius:12px;background:#eff6ff;color:#2563eb;text-align:center;padding:10px;font-weight:900}.admin-user-actions summary::-webkit-details-marker{display:none}.admin-user-actions[open] summary{margin-bottom:12px}.admin-user-form{grid-template-columns:1fr;gap:10px}.admin-user-form .select,.admin-user-form .input{width:100%;height:42px}.admin-user-form .btn{width:100%}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
