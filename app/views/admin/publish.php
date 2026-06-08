<?php $pageTitle='发布管理'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<div class="card">
  <h2>发布管理</h2>
  <div style="color:var(--text-soft);margin-top:6px;">管理完整包与更新包的发布、下架、删除状态</div>
</div>

<?php if (!empty($error)): ?><div class="card" style="background:#fee2e2;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="card" style="background:#dcfce7;color:#166534;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <h3 style="margin-bottom:12px;">签名公钥</h3>
  <div style="font-size:12px;color:var(--text-soft);margin-bottom:8px;">把这里的公钥复制到论坛后台「官方更新中心」配置里，用于验签。发布后的 diff / full 包都基于对应 zip 文件签名。</div>
  <textarea class="input break-all" rows="6" readonly><?php $cfg=require dirname(__DIR__,3).'/config/app.php'; $pub=(isset($cfg['sign_public_key']) && file_exists($cfg['sign_public_key']))?file_get_contents($cfg['sign_public_key']):''; echo htmlspecialchars((string)$pub); ?></textarea>
</div>

<div class="card">
  <h3 style="margin-bottom:12px;">新增发布记录</h3>
  <form method="post" enctype="multipart/form-data" class="grid" style="grid-template-columns:repeat(2,1fr);gap:12px;">
    <?= csrf_field() ?>
    <div>
      <label>类型</label>
      <select class="select" name="type">
        <option value="diff">更新包（diff）</option>
        <option value="full">完整包（full）</option>
      </select>
    </div>
    <div><label>版本号</label><input class="input" name="version" placeholder="1.0.0" required></div>
    <div><label>分支</label><input class="input" name="branch" value="main"></div>
    <div>
      <label>状态</label>
      <select class="select" name="status">
        <option value="published">发布</option>
        <option value="unpublished">下架</option>
      </select>
    </div>
    <div><label>更新包 zip</label><input class="input" type="file" name="package" required></div>
    <div><label>回滚包 zip（可选）</label><input class="input" type="file" name="rollback"></div>
    <div><label>完整包 zip（可选）</label><input class="input" type="file" name="full"></div>
    <div style="display:flex;gap:18px;align-items:center;flex-wrap:wrap;">
      <label><input type="checkbox" name="has_code" checked> 含代码</label>
      <label><input type="checkbox" name="has_db" checked> 含数据库</label>
    </div>
    <div><label>说明</label><input class="input" name="notes" placeholder="本次更新说明"></div>
    <div style="grid-column:1 / -1;"><button class="btn" type="submit">保存发布记录并签名</button></div>
  </form>
</div>

<div class="card">
  <h3>更新包版本记录</h3>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>版本</th><th>文件</th><th>回滚包</th><th>说明</th><th>签名</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
      <tbody>
      <?php foreach ($diffs as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><?= htmlspecialchars($p['version']) ?></td>
          <td class="break-all"><?= htmlspecialchars($p['filename']) ?></td>
          <td class="break-all"><?= htmlspecialchars((string)($p['rollback_filename'] ?? '')) ?></td>
          <td class="break-all"><?= htmlspecialchars((string)($p['notes'] ?? '')) ?></td>
          <td><?= !empty($p['signature']) ? '已签名' : '未签名' ?></td>
          <td><?= htmlspecialchars($p['status']) ?></td>
          <td class="break-all"><?= htmlspecialchars($p['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <form method="post" action="/admin.php?path=publish/toggle" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <select class="select" name="status" style="width:110px;">
                  <option value="published" <?= $p['status']==='published'?'selected':'' ?>>发布</option>
                  <option value="unpublished" <?= $p['status']==='unpublished'?'selected':'' ?>>下架</option>
                </select>
                <button class="btn btn-light" type="submit">保存</button>
              </form>
              <form method="post" action="/admin.php?path=publish/delete" onsubmit="return confirm('确认删除该更新包记录及服务器文件吗？');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-light" type="submit" style="color:#b91c1c;">删除</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <h3>完整包版本记录</h3>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>版本</th><th>文件</th><th>说明</th><th>签名</th><th>状态</th><th>full key 已使用</th><th>时间</th><th>操作</th></tr></thead>
      <tbody>
      <?php foreach ($fulls as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><?= htmlspecialchars($p['version']) ?></td>
          <td class="break-all"><?= htmlspecialchars((string)($p['full_filename'] ?: $p['filename'])) ?></td>
          <td class="break-all"><?= htmlspecialchars((string)($p['notes'] ?? '')) ?></td>
          <td><?= !empty($p['signature']) ? '已签名' : '未签名' ?></td>
          <td><?= htmlspecialchars($p['status']) ?></td>
          <td><?= !empty($p['full_key_used']) ? '是' : '否' ?></td>
          <td class="break-all"><?= htmlspecialchars($p['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <form method="post" action="/admin.php?path=publish/toggle" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <select class="select" name="status" style="width:110px;">
                  <option value="published" <?= $p['status']==='published'?'selected':'' ?>>发布</option>
                  <option value="unpublished" <?= $p['status']==='unpublished'?'selected':'' ?>>下架</option>
                </select>
                <button class="btn btn-light" type="submit">保存</button>
              </form>
              <form method="post" action="/admin.php?path=publish/delete" onsubmit="return confirm('确认删除该完整包记录及服务器文件吗？');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-light" type="submit" style="color:#b91c1c;">删除</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<style>
@media (max-width: 900px){
  form.grid[enctype="multipart/form-data"]{grid-template-columns:1fr !important;}
}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
