<?php $pageTitle='服务器迁移备份'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<style>.mig-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}.mig-warn{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:12px;padding:12px 14px;line-height:1.7}.mig-name{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px;word-break:break-all}.mig-table td{vertical-align:middle}</style>
<div class="card"><h2>服务器迁移备份</h2><p class="muted" style="margin-top:8px;line-height:1.7">生成一个完整迁移包，包含官方站源码、数据库 SQL、storage 与一次性迁移初始化标记。迁移包敏感，请只在可信环境保存。</p></div>
<?php if($message): ?><div class="card" style="background:#ecfdf5;color:#166534;border-color:#bbf7d0;"><?= htmlspecialchars($message) ?></div><?php endif; ?>
<?php if($error): ?><div class="card" style="background:#fef2f2;color:#b91c1c;border-color:#fecaca;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if(!empty($latestGenerated['name']) && !empty($latestGenerated['path']) && is_file((string)$latestGenerated['path'])): ?><div class="card" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;line-height:1.7">最新生成文件：<span class="mig-name"><?= htmlspecialchars((string)$latestGenerated['name']) ?></span><br>如果历史列表未刷新，可先用这个链接下载：<a class="btn btn-light" href="/admin.php?path=migration/download&name=<?= urlencode((string)$latestGenerated['name']) ?>">下载最新迁移包</a></div><?php endif; ?>
<?php if(!empty($status)): ?><div class="card" style="background:<?= (($status['state'] ?? '') === 'failed') ? '#fef2f2' : ((($status['state'] ?? '') === 'success') ? '#ecfdf5' : '#eff6ff') ?>;border-color:<?= (($status['state'] ?? '') === 'failed') ? '#fecaca' : ((($status['state'] ?? '') === 'success') ? '#bbf7d0' : '#bfdbfe') ?>;line-height:1.7"><b>生成状态：</b><?= htmlspecialchars((string)($status['message'] ?? $status['state'] ?? '')) ?><?php if(!empty($status['name'])): ?><br><span class="mig-name"><?= htmlspecialchars((string)$status['name']) ?></span><?php endif; ?><?php if(!empty($status['updated_at'])): ?><br><span class="muted">更新时间：<?= htmlspecialchars((string)$status['updated_at']) ?></span><?php endif; ?></div><?php endif; ?>
<div class="card mig-warn">迁移包不会分块。生成后请尽快下载并从旧服务器删除。新服务器首次访问 <code>/index.php?path=migration-setup</code>，输入生成时给出的迁移令牌完成一次性配置。</div>
<div class="card"><h3 style="margin-bottom:12px;">生成前检查</h3><div class="grid"><?php foreach(($preflight ?? []) as $c): ?><div style="display:flex;justify-content:space-between;gap:12px;border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:<?= !empty($c['ok']) ? '#f8fafc' : '#fef2f2' ?>;"><span><?= htmlspecialchars($c['label']) ?><br><small class="muted"><?= htmlspecialchars((string)$c['detail']) ?></small></span><b style="color:<?= !empty($c['ok']) ? '#16a34a' : '#dc2626' ?>"><?= !empty($c['ok']) ? '正常' : '异常' ?></b></div><?php endforeach; ?></div><form method="get" action="/admin.php" style="margin-top:12px;display:grid;gap:8px;"><input type="hidden" name="path" value="migration"><input class="input" name="mysqldump_path" value="<?= htmlspecialchars($mysqldumpPath ?? '') ?>" placeholder="手动填写 mysqldump 绝对路径后重新检查"><button class="btn btn-light" type="submit">重新检查</button></form></div>
<div class="card">
  <form method="post" action="/admin.php?path=migration" onsubmit="return confirm('确认生成完整迁移包？这可能耗时较久，且会包含敏感配置。')" class="grid">
    <?= csrf_field() ?><input type="hidden" name="_action" value="create">
    <label>mysqldump 路径（可选）<input class="input" name="mysqldump_path" value="<?= htmlspecialchars($mysqldumpPath ?? '') ?>" placeholder="例如 /www/server/mysql/bin/mysqldump"></label>
    <div class="muted">留空会自动探测：/usr/bin、/usr/local/mysql/bin、/www/server/mysql/bin、/www/server/mariadb/bin 等常见路径。</div>
    <button class="btn" id="migrationCreateBtn">生成完整迁移包</button>
  </form>
</div>
<div class="card"><h3 style="margin-bottom:12px;">历史迁移包</h3><div class="table-wrap"><table class="table mig-table"><thead><tr><th>文件</th><th>大小</th><th>时间</th><th>操作</th></tr></thead><tbody>
<?php foreach($packages as $p): ?><tr><td class="mig-name"><?= htmlspecialchars($p['name']) ?></td><td><?= number_format((int)$p['size']/1024/1024,2) ?> MB</td><td><?= htmlspecialchars($p['created_at']) ?><?php if(!empty($p['manifest']['created_at'])): ?><br><span class="muted">manifest: <?= htmlspecialchars((string)$p['manifest']['created_at']) ?></span><?php endif; ?></td><td class="mig-actions"><a class="btn btn-light" href="/admin.php?path=migration/download&name=<?= urlencode($p['name']) ?>">下载</a><form method="post" action="/admin.php?path=migration" onsubmit="return confirm('确认删除这个迁移包？')"><?= csrf_field() ?><input type="hidden" name="_action" value="delete"><input type="hidden" name="name" value="<?= htmlspecialchars($p['name']) ?>"><button class="btn btn-light" style="color:#dc2626;">删除</button></form></td></tr><?php endforeach; ?>
<?php if(!$packages): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:28px;">暂无迁移包</td></tr><?php endif; ?>
</tbody></table></div></div>
<script>
(function(){
  var btn=document.getElementById('migrationCreateBtn');
  if(!btn)return;
  var form=btn.closest('form');
  form.addEventListener('submit',function(){
    btn.disabled=true;
    btn.textContent='正在生成，请勿关闭页面...';
    var tip=document.createElement('div');
    tip.className='card';
    tip.style.background='#eff6ff';
    tip.style.borderColor='#bfdbfe';
    tip.style.color='#1d4ed8';
    tip.textContent='迁移包正在生成，源码和数据库较大时可能需要几十秒到数分钟。完成后页面会自动返回并显示下载链接。';
    form.parentNode.insertBefore(tip, form.nextSibling);
  });
})();
</script>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
