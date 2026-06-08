<?php
$pageTitle='版本发布';
$product = in_array(($product ?? ($_GET['product'] ?? 'claybbs')), ['claybbs','cutot'], true) ? ($product ?? ($_GET['product'] ?? 'claybbs')) : 'claybbs';
$productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
require dirname(__DIR__) . '/layouts/main.php';
?>
<div class=\"page-shell\">
<div class="card">
  <h2>版本发布</h2>
  <div style="color:var(--text-soft);margin-top:6px;line-height:1.7;">当前管理：<?= htmlspecialchars($productLabel) ?>。ClayBBS 与 CUTOT 的完整包、更新包、版本链分开管理，互不混用。</div>
</div>

<?php if (!empty($error)): ?><div class="card" style="background:#fee2e2;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="card" style="background:#dcfce7;color:#166534;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card product-tabs-card">
  <div class="product-tabs" role="tablist" aria-label="产品版本管理切换">
    <a class="product-tab <?= $product === 'claybbs' ? 'active' : '' ?>" href="/admin.php?path=fullpacks&product=claybbs">ClayBBS 版本与更新</a>
    <a class="product-tab <?= $product === 'cutot' ? 'active' : '' ?>" href="/admin.php?path=fullpacks&product=cutot">CUTOT 版本与更新</a>
  </div>
</div>


<div class="card">
  <h3 style="margin-bottom:12px;">上传 <?= htmlspecialchars($productLabel) ?> 完整包并自动构建更新包</h3>
  <form method="post" action="/admin.php?path=fullpacks" enctype="multipart/form-data" class="grid" style="grid-template-columns:1fr 1fr;gap:12px;">
    <?= csrf_field() ?>
    <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
    <div><label>版本号</label><input class="input" name="version" placeholder="1.0.0" required></div>
    <div><label>更新等级</label><select class="select" name="update_level"><option value="normal">普通更新</option><option value="security">安全更新</option><option value="critical">重要更新</option></select></div>
    <div><label>强制更新</label><select class="select" name="force_update"><option value="0">否</option><option value="1">是</option></select></div>
    <div><label>最低可升级版本</label><input class="input" name="min_version" placeholder="可留空"></div>
    <div><label>最高可升级版本</label><input class="input" name="max_version" placeholder="可留空"></div>
    <div style="grid-column:1 / -1;"><label>完整包 zip</label><input class="input" type="file" name="package" accept=".zip" required></div>
    <div style="grid-column:1 / -1;"><label>说明</label><textarea class="input release-notes-input" name="notes" rows="8" placeholder="本版本说明 / 更新日志，支持换行输入"></textarea></div>
    <div style="grid-column:1 / -1;color:var(--text-soft);font-size:13px;line-height:1.7;">
      上传后会写入 <?= htmlspecialchars($productLabel) ?> 独立版本树；从第二个完整包开始，仅与同产品的上一个完整包对比并生成 diff 更新包。
    </div>
    <div style="grid-column:1 / -1;"><button class="btn" type="submit">上传完整包并自动构建</button></div>
  </form>
</div>

<div class="card">
  <h3 style="margin-bottom:12px;">签名公钥</h3>
  <div style="font-size:12px;color:var(--text-soft);margin-bottom:8px;">论坛后台「官方更新中心」需要使用此公钥校验更新包签名。</div>
  <textarea class="input break-all" rows="6" readonly><?php $cfg=require dirname(__DIR__,3).'/config/app.php'; $pub=(isset($cfg['sign_public_key']) && file_exists($cfg['sign_public_key']))?file_get_contents($cfg['sign_public_key']):''; echo htmlspecialchars((string)$pub); ?></textarea>
</div>

<div class="card">
  <h3><?= htmlspecialchars($productLabel) ?> 完整包版本树</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>ID</th><th>版本</th><th>完整包</th><th>状态</th><th>说明</th><th>时间</th><th>操作</th></tr></thead>
    <tbody>
    <?php $latestFullId = (int)(($fulls[0]['id'] ?? $packages[0]['id'] ?? 0)); ?>
    <?php foreach (($fulls ?? $packages ?? []) as $pkg): $isLatestFull = (int)$pkg['id'] === $latestFullId; ?>
      <tr>
        <td><?= (int)$pkg['id'] ?></td>
        <td><strong><?= htmlspecialchars($pkg['version']) ?></strong></td>
        <td class="break-all"><?= htmlspecialchars((string)($pkg['full_filename'] ?: $pkg['filename'])) ?></td>
        <td><?= htmlspecialchars($pkg['status']) ?></td>
        <td class="break-all"><?= nl2br(htmlspecialchars((string)($pkg['notes'] ?? ''))) ?></td>
        <td><?= htmlspecialchars($pkg['created_at']) ?></td>
        <td>
          <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <a href="/admin.php?path=fullpacks/view&id=<?= (int)$pkg['id'] ?>">预览</a>
            <?php if ($isLatestFull): ?><a href="/index.php?path=download/full&id=<?= (int)$pkg['id'] ?>">下载最新版</a><?php else: ?><span style="color:var(--text-soft);font-size:12px;">历史完整包不可下载</span><?php endif; ?>
            <form method="post" action="/admin.php?path=fullpacks/delete" onsubmit="return confirm('确认删除该完整包、本地完整包文件，并同步删除关联更新包和回滚包吗？');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$pkg['id'] ?>">
              <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
              <button class="btn btn-light" type="submit" style="color:#b91c1c;padding:6px 10px;">删除</button>
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
  <h3><?= htmlspecialchars($productLabel) ?> 更新包版本树</h3>
  <div style="color:var(--text-soft);font-size:13px;margin:6px 0 12px;">这里显示由完整包自动对比生成的增量包。论坛后台热更新会下载这些包。</div>
  <div class="table-wrap">
    <table class="table">
      <thead><tr><th>ID</th><th>版本</th><th>更新包</th><th>回滚包</th><th>说明</th><th>签名</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
      <tbody>
      <?php foreach (($diffs ?? []) as $p): ?>
        <tr>
          <td><?= (int)$p['id'] ?></td>
          <td><strong><?= htmlspecialchars($p['version']) ?></strong><div style="font-size:12px;color:var(--text-soft);">from <?= htmlspecialchars((string)($p['from_version'] ?? '')) ?></div></td>
          <td class="break-all"><?= htmlspecialchars($p['filename']) ?></td>
          <td class="break-all"><?= htmlspecialchars((string)($p['rollback_filename'] ?? '')) ?></td>
          <td class="break-all"><?= nl2br(htmlspecialchars((string)($p['notes'] ?? ''))) ?></td>
          <td><?= !empty($p['signature']) ? '已签名' : '未签名' ?></td>
          <td><?= htmlspecialchars($p['status']) ?><div style="font-size:12px;color:var(--text-soft);"><?= htmlspecialchars((string)($p['update_level'] ?? 'normal')) ?><?= !empty($p['force_update']) ? ' / 强制' : '' ?></div></td>
          <td class="break-all"><?= htmlspecialchars($p['created_at']) ?></td>
          <td>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
              <form method="post" action="/admin.php?path=publish/toggle" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
                <select class="select" name="status" style="width:110px;">
                  <option value="published" <?= $p['status']==='published'?'selected':'' ?>>发布</option>
                  <option value="unpublished" <?= $p['status']==='unpublished'?'selected':'' ?>>下架</option>
                </select>
                <button class="btn btn-light" type="submit">保存</button>
              </form>
              <form method="post" action="/admin.php?path=publish/delete" onsubmit="return confirm('确认删除该更新包和回滚包吗？完整包不会被删除。');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
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
<style>.product-tabs-card{padding:10px}.product-tabs{display:flex;gap:8px;overflow-x:auto}.product-tab{display:inline-flex;align-items:center;height:38px;padding:0 14px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;font-weight:900;font-size:13px;white-space:nowrap}.product-tab.active,.product-tab:hover{background:#0284c7;border-color:#0284c7;color:#fff}.release-notes-input{min-height:180px;line-height:1.75;resize:vertical;white-space:pre-wrap;}@media (max-width: 900px){form.grid[enctype="multipart/form-data"]{grid-template-columns:1fr !important;}}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
