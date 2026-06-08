<?php
$siteCfg = (new \App\Models\SettingModel())->getSiteConfig();
$pageTitle = '应用中心';
$activeTab = (string)($_GET['tab'] ?? 'review');
if (!in_array($activeTab, ['review','appeals','developer_apps','apps','licenses','categories'], true)) { $activeTab = 'review'; }
require dirname(__DIR__) . '/layouts/main.php';
?>
<div class="card app-center-head">
  <div>
    <h2>应用中心</h2>
    <div class="muted" style="line-height:1.7;margin-top:6px;">集中管理应用审核、应用上下架和市场分类。内容已拆成 Tab，避免都挤在一个页面里。</div>
  </div>
  <div class="app-center-stats">
    <span><?= count($pendingVersions ?? []) ?> 待审</span>
    <span><?= count($pendingAppeals ?? []) ?> 申诉</span>
    <span><?= count($items ?? []) ?> 应用</span>
    <span><?= count($categories ?? []) ?> 分类</span>
  </div>
  <?php if (!empty($error)): ?><div class="app-center-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
</div>

<div class="card app-center-tabs">
  <a class="<?= $activeTab==='review'?'active':'' ?>" href="/admin.php?path=market&tab=review">审核队列</a>
  <a class="<?= $activeTab==='appeals'?'active':'' ?>" href="/admin.php?path=market&tab=appeals">应用申诉</a>
  <a class="<?= $activeTab==='developer_apps'?'active':'' ?>" href="/admin.php?path=market&tab=developer_apps">公益开发者申请</a>
  <a class="<?= $activeTab==='apps'?'active':'' ?>" href="/admin.php?path=market&tab=apps">应用管理</a>
  <a class="<?= $activeTab==='licenses'?'active':'' ?>" href="/admin.php?path=market&tab=licenses">应用授权</a>
  <a class="<?= $activeTab==='categories'?'active':'' ?>" href="/admin.php?path=market&tab=categories">分类管理</a>
</div>

<?php if ($activeTab === 'review'): ?>
<section class="card app-center-panel">
  <div class="admin-section-head"><div><h2>审核队列</h2><p>只显示开发者提交的待审核版本。</p></div><span class="badge"><?= count($pendingVersions ?? []) ?> 个待审</span></div>
  <div class="table-wrap"><table class="table"><thead><tr><th>应用</th><th>类型</th><th>分类</th><th>Slug</th><th>版本</th><th>开发者ID</th><th>更新说明</th><th>操作</th></tr></thead><tbody>
  <?php foreach (($pendingVersions ?? []) as $v): ?>
    <tr><td><?= htmlspecialchars($v['name']) ?></td><td><?= $v['type']==='theme'?'主题':'插件' ?></td><td><?= htmlspecialchars((string)($v['category_name'] ?? '未分类')) ?></td><td><?= htmlspecialchars($v['slug']) ?></td><td><?= htmlspecialchars($v['version']) ?></td><td><?= (int)($v['developer_user_id'] ?? 0) ?></td><td><?= htmlspecialchars((string)($v['changelog'] ?? '')) ?></td><td style="min-width:250px;"><form method="post" action="/admin.php?path=market&tab=review" style="display:grid;gap:6px;"><?= csrf_field() ?><input type="hidden" name="_action" value="review_version"><input type="hidden" name="version_id" value="<?= (int)$v['id'] ?>"><input class="input" name="review_note" placeholder="审核说明，可留空"><div style="display:flex;gap:6px;flex-wrap:wrap;"><button class="btn small" name="status" value="published">通过上架</button><button class="btn small btn-light" name="status" value="rejected">驳回</button></div></form></td></tr>
  <?php endforeach; ?>
  <?php if (empty($pendingVersions)): ?><tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px;">暂无待审核版本</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($activeTab === 'appeals'): ?>
<section class="card app-center-panel">
  <div class="admin-section-head"><div><h2>应用申诉</h2><p>开发者可对已下架应用提交申诉。申诉通过后应用会自动恢复上架。</p></div><span class="badge"><?= count($pendingAppeals ?? []) ?> 个待处理</span></div>
  <div class="table-wrap"><table class="table"><thead><tr><th>应用</th><th>类型</th><th>分类</th><th>开发者</th><th>当前状态</th><th>申诉理由</th><th>申诉状态</th><th>处理说明</th><th>操作</th></tr></thead><tbody>
  <?php foreach (($appeals ?? []) as $a): ?>
    <tr>
      <td><b><?= htmlspecialchars((string)($a['name'] ?? '应用已删除')) ?></b><div class="muted">slug: <?= htmlspecialchars((string)($a['slug'] ?? '')) ?> · v<?= htmlspecialchars((string)($a['version'] ?? '')) ?></div></td>
      <td><?= ($a['type'] ?? '')==='theme'?'主题':'插件' ?></td>
      <td><?= htmlspecialchars((string)($a['category_name'] ?? '未分类')) ?></td>
      <td><?= htmlspecialchars((string)($a['developer_name'] ?: preg_replace('/@.*$/', '', (string)($a['developer_email'] ?? '')) ?: ('ID ' . (int)($a['developer_user_id'] ?? 0)))) ?></td>
      <td><?= htmlspecialchars((string)($a['item_status'] ?? '')) ?></td>
      <td style="min-width:220px;white-space:pre-wrap;"><?= htmlspecialchars((string)($a['reason'] ?? '')) ?></td>
      <td><?= ['pending'=>'待处理','approved'=>'已通过','rejected'=>'已驳回'][$a['status'] ?? 'pending'] ?? htmlspecialchars((string)($a['status'] ?? '')) ?></td>
      <td><?= nl2br(htmlspecialchars((string)($a['review_note'] ?? ''))) ?></td>
      <td style="min-width:260px;">
        <?php if (($a['status'] ?? '') === 'pending'): ?>
          <form method="post" action="/admin.php?path=market&tab=appeals" style="display:grid;gap:6px;">
            <?= csrf_field() ?><input type="hidden" name="_action" value="review_appeal"><input type="hidden" name="appeal_id" value="<?= (int)$a['id'] ?>">
            <input class="input" name="review_note" placeholder="处理说明，可留空">
            <div style="display:flex;gap:6px;flex-wrap:wrap;"><button class="btn small" name="status" value="approved">通过并上架</button><button class="btn small btn-light" name="status" value="rejected">驳回申诉</button></div>
          </form>
        <?php else: ?><span class="muted"><?= htmlspecialchars((string)($a['reviewed_at'] ?? '')) ?></span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (empty($appeals)): ?><tr><td colspan="9" style="text-align:center;color:#94a3b8;padding:28px;">暂无应用申诉</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php endif; ?>


<?php if ($activeTab === 'developer_apps'): ?>
<section class="card app-center-panel">
  <div class="admin-section-head"><div><h2>公益开发者申请</h2><p>审核用户提交的公益开发者申请。通过后用户可发布免费插件/主题。</p></div></div>
  <div class="table-wrap"><table class="table"><thead><tr><th>用户</th><th>申请理由</th><th>状态</th><th>审核说明</th><th>时间</th><th>操作</th></tr></thead><tbody>
  <?php foreach (($developerApplications ?? []) as $a): ?><tr><td><?= htmlspecialchars((string)($a['user_name'] ?: $a['user_email'] ?: ('用户ID '.(int)$a['user_id']))) ?><div class="muted">ID <?= (int)$a['user_id'] ?> · <?= htmlspecialchars((string)($a['role'] ?? '')) ?> / <?= htmlspecialchars((string)($a['developer_level'] ?? '')) ?></div></td><td style="min-width:220px;white-space:pre-wrap;"><?= htmlspecialchars((string)($a['reason'] ?? '')) ?></td><td><?= ['pending'=>'待审核','approved'=>'已通过','rejected'=>'已驳回'][$a['status']] ?? htmlspecialchars((string)$a['status']) ?></td><td><?= htmlspecialchars((string)($a['review_note'] ?? '')) ?></td><td><?= htmlspecialchars((string)$a['created_at']) ?></td><td><?php if (($a['status'] ?? '')==='pending'): ?><form method="post" action="/admin.php?path=market&tab=developer_apps" style="display:grid;gap:6px;min-width:220px;"><?= csrf_field() ?><input type="hidden" name="_action" value="review_developer_application"><input type="hidden" name="application_id" value="<?= (int)$a['id'] ?>"><input class="input" name="review_note" placeholder="审核说明"><div style="display:flex;gap:6px;"><button class="btn small" name="status" value="approved">通过</button><button class="btn small btn-light" name="status" value="rejected">驳回</button></div></form><?php else: ?><span class="muted"><?= htmlspecialchars((string)($a['reviewed_at'] ?? '')) ?></span><?php endif; ?></td></tr><?php endforeach; ?>
  <?php if (empty($developerApplications)): ?><tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:28px;">暂无公益开发者申请</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($activeTab === 'apps'): ?>
<section class="card app-center-panel">
  <div class="admin-section-head"><div><h2>应用管理</h2><p>搜索、筛选、上下架或删除应用。</p></div></div>
  <form method="get" action="/admin.php" class="market-filter">
    <input type="hidden" name="path" value="market"><input type="hidden" name="tab" value="apps">
    <input class="input" name="q" value="<?= htmlspecialchars((string)($_GET['q'] ?? '')) ?>" placeholder="搜索应用名称或 slug">
    <select class="select" name="type"><option value="">全部类型</option><option value="plugin" <?= ($_GET['type'] ?? '')==='plugin'?'selected':'' ?>>插件</option><option value="theme" <?= ($_GET['type'] ?? '')==='theme'?'selected':'' ?>>主题</option></select>
    <select class="select" name="category_id"><option value="0">全部分类</option><?php foreach (($categories ?? []) as $cat): ?><option value="<?= (int)$cat['id'] ?>" <?= (int)($_GET['category_id'] ?? 0)===(int)$cat['id']?'selected':'' ?>><?= $cat['type']==='theme'?'主题':'插件' ?> / <?= htmlspecialchars($cat['name']) ?></option><?php endforeach; ?></select>
    <select class="select" name="status"><option value="">全部状态</option><?php foreach(['draft'=>'草稿','pending'=>'待审核','published'=>'已上架','rejected'=>'已驳回','hidden'=>'已下架'] as $k=>$v): ?><option value="<?= $k ?>" <?= ($_GET['status'] ?? '')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?></select>
    <button class="btn" type="submit">搜索</button><a class="btn btn-light" href="/admin.php?path=market&tab=apps">重置</a>
  </form>
  <div class="table-wrap"><table class="table"><thead><tr><th>类型</th><th>分类</th><th>名称</th><th>Slug</th><th>当前版本</th><th>开发者ID</th><th>价格</th><th>状态</th><th>下载</th><th>操作</th></tr></thead><tbody>
  <?php foreach (($items ?? []) as $it): ?>
    <tr><td><?= $it['type']==='theme'?'主题':'插件' ?></td><td><?= htmlspecialchars((string)($it['category_name'] ?? '未分类')) ?></td><td><?= htmlspecialchars($it['name']) ?></td><td><?= htmlspecialchars($it['slug']) ?></td><td><?= htmlspecialchars($it['version']) ?></td><td><?= (int)($it['developer_user_id'] ?? 0) ?></td><td><?= (float)$it['price'] > 0 ? '￥'.htmlspecialchars((string)$it['price']) : '免费' ?></td><td><?= htmlspecialchars($it['status']) ?></td><td><?= (int)$it['downloads'] ?></td><td style="display:flex;gap:6px;flex-wrap:wrap;">
      <?php if ($it['status'] === 'published' || $it['status'] === 'hidden'): ?><form method="post" action="/admin.php?path=market&tab=apps"><?= csrf_field() ?><input type="hidden" name="_action" value="toggle"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><input type="hidden" name="status" value="<?= $it['status']==='published'?'hidden':'published' ?>"><button class="btn small"><?= $it['status']==='published'?'下架':'上架' ?></button></form><?php endif; ?>
      <form method="post" action="/admin.php?path=market&tab=apps" onsubmit="return confirm('确定删除该应用？')"><?= csrf_field() ?><input type="hidden" name="_action" value="delete"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>"><button class="btn small danger">删除</button></form>
    </td></tr>
  <?php endforeach; ?>
  <?php if (empty($items)): ?><tr><td colspan="10" style="text-align:center;color:#94a3b8;padding:28px;">没有匹配的应用</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php endif; ?>


<?php if ($activeTab === 'licenses'): ?>
<section class="card app-center-panel">
  <div class="admin-section-head"><div><h2>应用授权</h2><p>可直接把应用授予给用户；付费应用下载前仍需绑定域名，必要时可在这里解除绑定。</p></div></div>
  <form method="post" action="/admin.php?path=market&tab=licenses" class="grant-license-form" id="grantLicenseForm">
    <?= csrf_field() ?><input type="hidden" name="_action" value="grant_license"><input type="hidden" name="user_id" id="grantUserId"><input type="hidden" name="item_id" id="grantItemId">
    <div class="grant-picker"><span class="grant-label">搜索用户</span><input class="input grant-search" id="grantUserSearch" autocomplete="off" placeholder="输入用户 ID / 邮箱 / 昵称" required><div class="grant-results" id="grantUserResults"><?php foreach (($grantUsers ?? []) as $u): ?><?php $userLabel = '#' . (int)$u['id'] . ' ' . (string)($u['name'] ?: $u['email']); $userKey = mb_strtolower($userLabel . ' ' . (string)($u['email'] ?? '') . ' ' . (string)($u['name'] ?? '')); ?><button type="button" class="grant-option" data-id="<?= (int)$u['id'] ?>" data-label="<?= htmlspecialchars($userLabel) ?>" data-keyword="<?= htmlspecialchars($userKey) ?>"><?= htmlspecialchars($userLabel) ?></button><?php endforeach; ?></div><div class="grant-empty">没有匹配的用户</div></div>
    <div class="grant-picker"><span class="grant-label">搜索应用</span><input class="input grant-search" id="grantItemSearch" autocomplete="off" placeholder="输入应用名称 / slug / 版本" required><div class="grant-results" id="grantItemResults"><?php foreach (($allMarketItems ?? []) as $it): ?><?php $itemLabel = '#' . (int)$it['id'] . ' ' . (($it['type'] ?? '')==='theme'?'主题':'插件') . ' / ' . (string)$it['name'] . ' / ' . (string)$it['slug'] . ' / v' . (string)$it['version'] . ((float)($it['price'] ?? 0)>0?' / ￥'.(string)$it['price']:' / 免费'); $itemKey = mb_strtolower($itemLabel); ?><button type="button" class="grant-option" data-id="<?= (int)$it['id'] ?>" data-label="<?= htmlspecialchars($itemLabel) ?>" data-keyword="<?= htmlspecialchars($itemKey) ?>"><?= htmlspecialchars($itemLabel) ?></button><?php endforeach; ?></div><div class="grant-empty">没有匹配的应用</div></div>
    <label><span class="grant-label">绑定域名</span><input class="input" name="bound_domain" placeholder="可选：example.com"></label>
    <button class="btn" type="submit">授予应用</button>
  </form>
  <form method="get" action="/admin.php" class="market-filter"><input type="hidden" name="path" value="market"><input type="hidden" name="tab" value="licenses"><input class="input" name="license_q" value="<?= htmlspecialchars((string)($_GET['license_q'] ?? '')) ?>" placeholder="搜索授权 Key / 用户 / 应用 / 域名"><button class="btn" type="submit">搜索</button><a class="btn btn-light" href="/admin.php?path=market&tab=licenses">重置</a></form>
  <div class="table-wrap"><table class="table"><thead><tr><th>授权 Key</th><th>用户</th><th>应用</th><th>价格</th><th>绑定域名</th><th>状态</th><th>时间</th><th>操作</th></tr></thead><tbody>
  <?php foreach (($licenses ?? []) as $lic): ?><tr><td class="break-all"><code><?= htmlspecialchars((string)$lic['license_key']) ?></code></td><td>#<?= (int)$lic['user_id'] ?> <?= htmlspecialchars((string)($lic['user_name'] ?: $lic['user_email'])) ?></td><td><?= ($lic['type'] ?? '')==='theme'?'主题':'插件' ?> / <?= htmlspecialchars((string)$lic['name']) ?><div class="muted">slug: <?= htmlspecialchars((string)$lic['slug']) ?> · v<?= htmlspecialchars((string)$lic['version']) ?></div></td><td><?= (float)($lic['price'] ?? 0)>0?'￥'.htmlspecialchars((string)$lic['price']):'免费' ?></td><td><?= htmlspecialchars((string)($lic['bound_domain'] ?? '')) ?: '<span class="muted">未绑定</span>' ?></td><td><?= htmlspecialchars((string)$lic['status']) ?></td><td><?= htmlspecialchars((string)$lic['created_at']) ?></td><td><?php if (!empty($lic['bound_domain'])): ?><form method="post" action="/admin.php?path=market&tab=licenses" onsubmit="return confirm('确定解除该授权绑定域名？')"><?= csrf_field() ?><input type="hidden" name="_action" value="unbind_license"><input type="hidden" name="license_id" value="<?= (int)$lic['id'] ?>"><button class="btn small btn-light" type="submit">解除绑定</button></form><?php else: ?><span class="muted">—</span><?php endif; ?></td></tr><?php endforeach; ?>
  <?php if (empty($licenses)): ?><tr><td colspan="8" style="text-align:center;color:#94a3b8;padding:28px;">暂无应用授权</td></tr><?php endif; ?>
  </tbody></table></div>
</section>
<?php endif; ?>

<?php if ($activeTab === 'categories'): ?>
<section class="card app-center-panel">
  <div class="admin-section-head"><div><h2>分类管理</h2><p>分别配置插件分类与主题分类。隐藏后的分类不会出现在开发者创建应用选项中，已有应用仍保留分类显示。</p></div></div>
  <form method="post" action="/admin.php?path=market&tab=categories" class="category-form">
    <?= csrf_field() ?><input type="hidden" name="_action" value="save_category">
    <select class="select" name="category_type" required><option value="plugin">插件分类</option><option value="theme">主题分类</option></select>
    <input class="input" name="name" placeholder="分类名称，例如：内容增强" required>
    <input class="input" name="slug" placeholder="slug，例如：content">
    <input class="input" type="number" name="sort_order" min="0" value="10" placeholder="排序">
    <select class="select" name="status"><option value="active">启用</option><option value="hidden">隐藏</option></select>
    <button class="btn" type="submit">添加分类</button>
  </form>
  <div class="category-columns">
    <?php foreach ([['plugin','插件分类',$pluginCategories ?? []], ['theme','主题分类',$themeCategories ?? []]] as $group): ?>
      <div class="category-panel">
        <h3><?= $group[1] ?></h3>
        <?php foreach ($group[2] as $cat): ?>
          <div class="category-item">
            <form method="post" action="/admin.php?path=market&tab=categories" class="category-edit-form">
              <?= csrf_field() ?><input type="hidden" name="_action" value="save_category"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><input type="hidden" name="category_type" value="<?= htmlspecialchars($group[0]) ?>">
              <input class="input" name="name" value="<?= htmlspecialchars($cat['name']) ?>" required>
              <input class="input" name="slug" value="<?= htmlspecialchars($cat['slug']) ?>" required>
              <input class="input" type="number" name="sort_order" min="0" value="<?= (int)$cat['sort_order'] ?>">
              <select class="select" name="status"><option value="active" <?= $cat['status']==='active'?'selected':'' ?>>启用</option><option value="hidden" <?= $cat['status']==='hidden'?'selected':'' ?>>隐藏</option></select>
              <button class="btn small btn-light" type="submit">保存</button>
            </form>
            <div class="category-actions">
              <form method="post" action="/admin.php?path=market&tab=categories"><?= csrf_field() ?><input type="hidden" name="_action" value="toggle_category"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><input type="hidden" name="status" value="<?= $cat['status']==='active'?'hidden':'active' ?>"><button class="btn small btn-light" type="submit"><?= $cat['status']==='active'?'隐藏':'启用' ?></button></form>
              <form method="post" action="/admin.php?path=market&tab=categories" onsubmit="return confirm('确定删除该分类？已有应用会转入默认分类或未分类。')"><?= csrf_field() ?><input type="hidden" name="_action" value="delete_category"><input type="hidden" name="id" value="<?= (int)$cat['id'] ?>"><button class="btn small danger" type="submit">删除</button></form>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>


<script>
(function(){
  var form=document.getElementById('grantLicenseForm');
  if(!form)return;
  function bind(inputId,resultsId,hiddenId,label){
    var input=document.getElementById(inputId),results=document.getElementById(resultsId),hidden=document.getElementById(hiddenId);
    if(!input||!results||!hidden)return;
    var picker=input.closest('.grant-picker');
    var empty=picker?picker.querySelector('.grant-empty'):null;
    var options=[].slice.call(results.querySelectorAll('.grant-option'));
    function filter(){
      hidden.value='';
      var q=(input.value||'').trim().toLowerCase();
      var shown=0;
      options.forEach(function(opt){
        var key=(opt.dataset.keyword||opt.textContent||'').toLowerCase();
        var ok=q!=='' && key.indexOf(q)!==-1;
        opt.style.display=ok?'block':'none';
        if(ok&&shown<30){shown++;}else if(ok){opt.style.display='none';}
      });
      results.classList.toggle('open',q!==''&&shown>0);
      if(empty)empty.classList.toggle('open',q!==''&&shown===0);
    }
    input.addEventListener('input',filter);
    input.addEventListener('focus',filter);
    options.forEach(function(opt){opt.addEventListener('click',function(){input.value=opt.dataset.label||opt.textContent.trim();hidden.value=opt.dataset.id||'';results.classList.remove('open');if(empty)empty.classList.remove('open');});});
    document.addEventListener('click',function(e){if(!picker||picker.contains(e.target))return;results.classList.remove('open');if(empty)empty.classList.remove('open');});
    form.addEventListener('submit',function(e){if(!hidden.value){e.preventDefault();alert('请选择有效的'+label);input.focus();filter();}});
  }
  bind('grantUserSearch','grantUserResults','grantUserId','用户');
  bind('grantItemSearch','grantItemResults','grantItemId','应用');
})();
</script>

<style>.app-center-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start;flex-wrap:wrap}.app-center-head h2{font-size:30px;margin:0}.app-center-stats{display:flex;gap:8px;flex-wrap:wrap}.app-center-stats span{border:1px solid var(--line);background:#f8fafc;border-radius:999px;padding:7px 10px;color:#64748b;font-size:12px;font-weight:900}.app-center-error{flex-basis:100%;color:#b91c1c;background:#fee2e2;padding:10px;border-radius:10px}.app-center-tabs{display:flex;gap:8px;flex-wrap:wrap;padding:10px}.app-center-tabs a{padding:9px 14px;border-radius:10px;color:#64748b;background:#f8fafc;text-decoration:none;font-weight:900;font-size:14px}.app-center-tabs a.active,.app-center-tabs a:hover{background:#2563eb;color:#fff}.app-center-panel{margin-top:0}.admin-section-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start;margin-bottom:14px}.admin-section-head h2{margin:0}.admin-section-head p{color:#64748b;font-size:13px;margin-top:6px}.category-form{display:grid;grid-template-columns:130px minmax(160px,1fr) minmax(140px,1fr) 90px 100px auto;gap:10px;align-items:center;margin-bottom:14px}.category-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.category-panel{border:1px solid var(--line);border-radius:14px;padding:12px;background:#f8fafc}.category-panel h3{margin:0 0 10px}.category-item{background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px;margin-top:10px}.category-edit-form{display:grid;grid-template-columns:minmax(120px,1fr) minmax(110px,1fr) 76px 92px auto;gap:8px;align-items:center}.category-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}.grant-license-form{display:grid;grid-template-columns:minmax(190px,1fr) minmax(250px,1.2fr) minmax(180px,1fr) auto;gap:10px;margin-bottom:14px;align-items:end}.grant-license-form label,.grant-picker{display:grid;gap:4px;position:relative}.grant-label{font-size:12px;color:#64748b;font-weight:900}.grant-results,.grant-empty{display:none;position:absolute;left:0;right:0;top:100%;z-index:30;margin-top:4px;max-height:260px;overflow:auto;border:1px solid #dbeafe;border-radius:12px;background:#fff;box-shadow:0 18px 42px rgba(15,23,42,.16);padding:6px}.grant-results.open,.grant-empty.open{display:block}.grant-option{width:100%;border:0;background:transparent;text-align:left;padding:9px 10px;border-radius:9px;color:#334155;font-size:13px;cursor:pointer}.grant-option:hover{background:#eff6ff;color:#1d4ed8}.grant-empty{color:#94a3b8;font-size:13px;padding:12px}.market-filter{display:grid;grid-template-columns:minmax(200px,1fr) 120px 160px 130px auto auto;gap:10px;margin-bottom:14px;align-items:center}.order-summary{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;margin:12px 0 14px}.order-summary div{background:#f8fafc;border:1px solid var(--line);border-radius:12px;padding:12px}.order-summary b{display:block;font-size:22px}.order-summary span{color:#64748b;font-size:12px}.order-status{display:inline-flex;border-radius:999px;padding:4px 8px;font-size:12px;font-weight:900;background:#f1f5f9;color:#64748b}.order-status.paid{background:#dcfce7;color:#166534}.order-status.pending{background:#fef3c7;color:#92400e}.order-status.closed{background:#fee2e2;color:#991b1b}.order-action{display:grid;gap:6px}.danger{background:#fee2e2!important;color:#991b1b!important}.danger:hover{background:#fecaca!important}.app-center-panel .table{min-width:980px}.app-center-panel .table th,.app-center-panel .table td{white-space:nowrap;word-break:normal;overflow-wrap:normal}.app-center-panel .table td:nth-child(6),.app-center-panel .table td:nth-child(8){white-space:normal;min-width:180px;max-width:260px;overflow-wrap:anywhere}.app-center-panel .table-wrap{overflow-x:auto;-webkit-overflow-scrolling:touch}@media(max-width:1060px){.category-form,.category-edit-form,.market-filter,.grant-license-form{grid-template-columns:1fr}.category-columns{grid-template-columns:1fr}}@media(max-width:640px){.app-center-head{gap:10px}.app-center-head h2{font-size:24px}.app-center-head .muted{font-size:13px;line-height:1.6}.app-center-stats span{padding:5px 8px;font-size:11px}.app-center-tabs{display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:8px}.app-center-tabs a{padding:8px 9px;font-size:13px;text-align:center}.admin-section-head{display:grid;gap:8px}.admin-section-head h2{font-size:22px}.admin-section-head p{font-size:12px;line-height:1.5}.app-center-panel{padding:12px!important}.app-center-panel .table{min-width:1040px}.app-center-panel .table th,.app-center-panel .table td{font-size:12px;padding:8px 10px}.badge{padding:5px 8px;font-size:11px}}</style>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
