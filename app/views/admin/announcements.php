<?php $pageTitle='公告管理'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<div class="card">
  <h2>公告管理</h2>
  <div class="muted">管理首页公告栏，支持蓝色普通公告、黄色紧急公告、红色危险公告。</div>
</div>

<?php if (!empty($error)): ?><div class="card" style="background:#fee2e2;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="card" style="background:#dcfce7;color:#166534;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card">
  <h3 style="margin-bottom:12px;"><?= $editItem ? '编辑公告' : '新增公告' ?></h3>
  <form method="post" class="grid" style="grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;">
    <?= csrf_field() ?>
    <input type="hidden" name="_action" value="save">
    <input type="hidden" name="id" value="<?= (int)($editItem['id'] ?? 0) ?>">
    <div><label>公告标题</label><input class="input" name="title" value="<?= htmlspecialchars((string)($editItem['title'] ?? '')) ?>" required></div>
    <div><label>排序</label><input class="input" type="number" name="sort_order" value="<?= (int)($editItem['sort_order'] ?? 0) ?>"></div>
    <div>
      <label>公告类型</label>
      <select class="select" name="level">
        <?php $lv=(string)($editItem['level'] ?? 'info'); ?>
        <option value="info" <?= $lv==='info'?'selected':'' ?>>蓝色普通公告</option>
        <option value="warning" <?= $lv==='warning'?'selected':'' ?>>黄色紧急公告</option>
        <option value="danger" <?= $lv==='danger'?'selected':'' ?>>红色危险公告</option>
      </select>
    </div>
    <div>
      <label>状态</label>
      <?php $st=(string)($editItem['status'] ?? 'active'); ?>
      <select class="select" name="status">
        <option value="active" <?= $st==='active'?'selected':'' ?>>显示</option>
        <option value="hidden" <?= $st==='hidden'?'selected':'' ?>>隐藏</option>
      </select>
    </div>
    <div style="grid-column:1 / -1;"><label>公告内容</label><textarea class="input" name="content" rows="5" placeholder="填写公告详细内容，可为空"><?= htmlspecialchars((string)($editItem['content'] ?? '')) ?></textarea></div>
    <div style="grid-column:1 / -1;display:flex;gap:10px;flex-wrap:wrap;">
      <button class="btn" type="submit"><?= $editItem ? '保存修改' : '新增公告' ?></button>
      <?php if ($editItem): ?><a class="btn btn-light" href="/admin.php?path=announcements">取消编辑</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="card">
  <h3>公告列表</h3>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>标题</th><th>类型</th><th>状态</th><th>排序</th><th>更新时间</th><th>操作</th></tr></thead>
      <tbody>
      <?php foreach ($items as $item): ?>
        <tr>
          <td><?= (int)$item['id'] ?></td>
          <td class="break-all"><?= htmlspecialchars($item['title']) ?></td>
          <td>
            <?php if (($item['level'] ?? 'info') === 'warning'): ?>黄色紧急公告
            <?php elseif (($item['level'] ?? 'info') === 'danger'): ?>红色危险公告
            <?php else: ?>蓝色普通公告<?php endif; ?>
          </td>
          <td><?= ($item['status'] ?? 'active') === 'active' ? '显示' : '隐藏' ?></td>
          <td><?= (int)($item['sort_order'] ?? 0) ?></td>
          <td class="break-all"><?= htmlspecialchars((string)($item['updated_at'] ?? $item['created_at'] ?? '')) ?></td>
          <td>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
              <a class="btn btn-light" href="/admin.php?path=announcements&edit=<?= (int)$item['id'] ?>">编辑</a>
              <form method="post" action="/admin.php?path=announcements" onsubmit="return confirm('确认删除该公告吗？');">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
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
  form.grid{grid-template-columns:1fr !important;}
}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
