<?php $pageTitle='开发者中心'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<?php
$items = $items ?? [];
$pluginCategories = $pluginCategories ?? [];
$themeCategories = $themeCategories ?? [];
$appealMap = $appealMap ?? [];
$imageMap = $imageMap ?? [];
$totalApps = count($items);
$pendingCount = 0; $publishedCount = 0; $draftCount = 0; $hiddenCount = 0;
foreach ($items as $it) {
    if (($it['status'] ?? '') === 'pending') $pendingCount++;
    elseif (($it['status'] ?? '') === 'published') $publishedCount++;
    elseif (($it['status'] ?? '') === 'draft') $draftCount++;
    elseif (($it['status'] ?? '') === 'hidden') $hiddenCount++;
}
function market_category_options(array $categories, int $selected = 0, string $type = ''): string { ob_start(); foreach ($categories as $cat): ?><option value="<?= (int)$cat['id'] ?>" data-type="<?= htmlspecialchars($type !== '' ? $type : (string)($cat['type'] ?? '')) ?>" <?= (int)$cat['id']===$selected?'selected':'' ?>><?= htmlspecialchars($cat['name']) ?></option><?php endforeach; return trim(ob_get_clean()); }
function app_status_label(string $status): string { return ['draft'=>'草稿','pending'=>'待审核','published'=>'已上架','rejected'=>'已驳回','hidden'=>'已下架'][$status] ?? $status; }
function app_status_class(string $status): string { return in_array($status, ['published','pending','rejected','hidden'], true) ? $status : 'draft'; }
function version_status_label(string $status): string { return ['pending'=>'待审核','published'=>'已通过','rejected'=>'已驳回'][$status] ?? $status; }
?>
<section class="developer-hero card">
  <div class="developer-hero-copy">
    <span class="badge">Developer Console</span>
    <h1>面向 ClayBBS 生态的应用发布控制台</h1>
    <p>在这里创建插件与主题、提交版本审核、维护展示素材，并跟踪销售收入与提现进度。应用列表默认聚焦最新版本，历史审核记录可进入版本历史查看。</p>
    <div class="developer-hero-actions">
      <a class="btn" href="#create-app">创建应用</a>
      <a class="btn btn-light" href="/index.php?path=devdocs">查看开发文档</a>
    </div>
  </div>
  <div class="developer-hero-card">
    <div class="hero-card-top"><span>账户状态</span><strong><?= htmlspecialchars(\App\Models\UserModel::developerLevelLabel((string)($_SESSION['auth_user']['developer_level'] ?? 'none'))) ?></strong></div>
    <div class="hero-metrics">
      <div><b><?= $totalApps ?></b><span>应用</span></div>
      <div><b><?= $publishedCount ?></b><span>已上架</span></div>
      <div><b>￥<?= htmlspecialchars(number_format((float)($balance['available'] ?? 0), 2, '.', '')) ?></b><span>可提现</span></div>
    </div>
  </div>
</section>
<?php if (!empty($error)): ?><div class="card dev-alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>


<?php if (($_SESSION['auth_user']['developer_level'] ?? '') === 'public'): ?>
<div class="card upgrade-dev-card">
  <div><h2>升级为普通开发者</h2><div class="muted">公益开发者只能发布免费应用。升级后可发布付费插件/主题，并使用销售收益与提现功能。</div></div>
  <form method="post" action="/index.php?path=developer" data-no-ajax><?= csrf_field() ?><input type="hidden" name="_action" value="upgrade_normal"><button class="btn" type="submit">升级普通开发者 ￥<?= htmlspecialchars(number_format((float)($settings['developer_join_price'] ?? 99), 2, '.', '')) ?></button></form>
</div>
<?php endif; ?>

<div class="dev-tabs card"><button class="dev-tab active" data-tab="overview" type="button">概览</button><button class="dev-tab" data-tab="apps" type="button">我的应用</button><button class="dev-tab" data-tab="sales" type="button">销售订单</button><button class="dev-tab" data-tab="withdrawals" type="button">提现记录</button></div>

<section class="dev-panel active" data-panel="overview">
<div class="dev-stat-grid">
  <div class="card dev-stat"><span>可提现</span><b>￥<?= htmlspecialchars(number_format((float)($balance['available'] ?? 0), 2, '.', '')) ?></b></div>
  <div class="card dev-stat"><span>累计收入</span><b>￥<?= htmlspecialchars(number_format((float)($balance['income'] ?? 0), 2, '.', '')) ?></b></div>
  <div class="card dev-stat"><span>我的应用</span><b><?= $totalApps ?></b></div>
  <div class="card dev-stat"><span>待审核</span><b><?= $pendingCount ?></b></div>
  <div class="card dev-stat"><span>已上架</span><b><?= $publishedCount ?></b></div>
  <div class="card dev-stat"><span>已下架</span><b><?= $hiddenCount ?></b></div>
</div>
</section>

<section class="dev-panel" data-panel="withdrawals">
<div class="card dev-money-panel">
  <h2>销售与提现</h2>
  <div class="muted">当前分成：开发者 <?= htmlspecialchars((string)($settings['developer_share_ratio'] ?? '70')) ?>%，最低提现 ￥<?= htmlspecialchars(number_format((float)($settings['developer_min_withdraw'] ?? 10), 2, '.', '')) ?>。</div>
  <form method="post" action="/index.php?path=developer" class="withdraw-form">
    <?= csrf_field() ?><input type="hidden" name="_action" value="withdraw">
    <input class="input" type="number" step="0.01" name="amount" placeholder="提现金额" max="<?= htmlspecialchars((string)($balance['available'] ?? 0)) ?>">
    <input class="input" name="account_name" placeholder="收款姓名">
    <input class="input" name="account_no" placeholder="支付宝账号">
    <button class="btn" type="submit">申请提现</button>
  </form>
  <div class="table-wrap" style="margin-top:14px;"><table class="table"><thead><tr><th>金额</th><th>账号</th><th>状态</th><th>说明</th><th>时间</th></tr></thead><tbody><?php foreach (($withdrawals ?? []) as $w): ?><tr><td>￥<?= htmlspecialchars(number_format((float)$w['amount'],2,'.','')) ?></td><td><?= htmlspecialchars((string)$w['account_name']) ?> / <?= htmlspecialchars((string)$w['account_no']) ?></td><td><?= ['pending'=>'待审核','paid'=>'已打款','rejected'=>'已驳回'][$w['status']] ?? htmlspecialchars((string)$w['status']) ?></td><td><?= htmlspecialchars((string)($w['review_note'] ?? '')) ?></td><td><?= htmlspecialchars((string)$w['created_at']) ?></td></tr><?php endforeach; ?><?php if (empty($withdrawals)): ?><tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:20px;">暂无提现记录</td></tr><?php endif; ?></tbody></table></div>
</div>
</section>

<section class="dev-panel" data-panel="sales">
<div class="card"><h2>销售订单</h2><div class="muted">展示已支付应用订单，以及按分成比例计算后的开发者收入。</div><div class="table-wrap" style="margin-top:12px;"><table class="table"><thead><tr><th>订单</th><th>应用</th><th>买家</th><th>订单金额</th><th>我的收入</th><th>时间</th></tr></thead><tbody><?php foreach (($sales ?? []) as $sale): ?><tr><td class="break-all"><code><?= htmlspecialchars((string)$sale['order_no']) ?></code></td><td><?= htmlspecialchars((string)$sale['name']) ?></td><td><?= htmlspecialchars((string)($sale['buyer_name'] ?: $sale['buyer_email'])) ?></td><td>￥<?= htmlspecialchars(number_format((float)$sale['amount'],2,'.','')) ?></td><td>￥<?= htmlspecialchars(number_format((float)$sale['developer_amount'],2,'.','')) ?></td><td><?= htmlspecialchars((string)$sale['paid_at']) ?></td></tr><?php endforeach; ?><?php if (empty($sales)): ?><tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">暂无出售记录</td></tr><?php endif; ?></tbody></table></div></div>
</section>

<section class="dev-panel" data-panel="apps">
<div class="dev-main-panel">
  <details class="card create-app-box" id="create-app">
    <summary>+ 创建应用</summary>
    <div class="section-head"><div><h2>创建应用</h2><p>创建应用本体，不需要立即上传 ZIP。版本包在应用创建后单独提交。</p></div></div>
    <form method="post" enctype="multipart/form-data" action="/index.php?path=developer" class="dev-form" data-no-ajax>
      <?= csrf_field() ?><input type="hidden" name="_action" value="create_app">
      <label>应用类型<select class="select js-market-type" name="type" required><option value="plugin">插件</option><option value="theme">主题模板</option></select></label>
      <label>应用分类<select class="select js-market-category" name="category_id" required data-plugin-options="<?= htmlspecialchars(market_category_options($pluginCategories, 0, 'plugin'), ENT_QUOTES, 'UTF-8') ?>" data-theme-options="<?= htmlspecialchars(market_category_options($themeCategories, 0, 'theme'), ENT_QUOTES, 'UTF-8') ?>"></select></label>
      <label>应用名称<input class="input" name="name" required placeholder="例如：积分商城"></label>
      <label>Slug<input class="input" name="slug" required placeholder="points-shop，只能英文/数字/短横线"></label>
      <label>应用 Logo<input class="input" type="file" name="logo" accept="image/*"></label>
      <label>展示图片（可一次选择多张）<input class="input" type="file" name="gallery[]" accept="image/*" multiple><small>按住 Ctrl/Shift 或在手机相册中多选，可一次上传多张展示图。</small></label>
      <?php if (($_SESSION['auth_user']['developer_level'] ?? '') === 'public'): ?><div class="muted public-price-note">公益开发者创建的应用默认为免费。</div><?php else: ?><label>价格<input class="input" name="price" type="number" step="0.01" value="0"></label><?php endif; ?>
      <label style="grid-column:1/-1;">应用介绍<textarea class="input" name="description" rows="4" placeholder="说明功能、适配版本、使用场景"></textarea></label>
      <button class="btn" type="submit">创建应用</button>
    </form>
  </details>

  <section id="my-apps" class="dev-app-list">
  <?php if (!empty($items)): foreach ($items as $app): ?>
    <?php
      $appType = (string)($app['type'] ?? 'plugin');
      $appCategories = $appType === 'theme' ? $themeCategories : $pluginCategories;
      $appVersions = $versions[(int)$app['id']] ?? [];
      $latestVersion = $appVersions[0] ?? null;
      $historyCount = count($appVersions);
      $appImages = $imageMap[(int)$app['id']] ?? [];
      $status = (string)($app['status'] ?? 'draft');
    ?>
    <article class="card dev-app-card app-status-<?= htmlspecialchars(app_status_class($status)) ?>">
      <div class="dev-app-top">
        <?php if (!empty($app['logo'])): ?><img class="app-logo" src="<?= htmlspecialchars($app['logo']) ?>" alt=""><?php else: ?><div class="app-logo app-logo-fallback"><?= $appType==='theme'?'T':'P' ?></div><?php endif; ?>
        <div class="dev-app-main">
          <div class="dev-app-tags">
            <span class="type-pill"><?= $appType==='theme'?'主题模板':'插件' ?></span>
            <?php if (!empty($app['category_name'])): ?><span class="category-pill"><?= htmlspecialchars((string)$app['category_name']) ?></span><?php endif; ?>
            <span class="status-pill <?= htmlspecialchars(app_status_class($status)) ?>"><?= htmlspecialchars(app_status_label($status)) ?></span>
          </div>
          <h3><?= htmlspecialchars($app['name']) ?></h3>
          <div class="app-subline">slug: <?= htmlspecialchars($app['slug']) ?> · 当前版本：<?= htmlspecialchars($app['version']) ?> · 作者：<?= htmlspecialchars((string)($app['author'] ?? '')) ?></div>
        </div>
        <div class="dev-app-price"><strong><?= (float)($app['price'] ?? 0) > 0 ? '￥' . htmlspecialchars((string)$app['price']) : '免费' ?></strong><span><?= (int)($app['downloads'] ?? 0) ?> 下载</span></div>
      </div>

      <div class="app-desc"><?= nl2br(htmlspecialchars((string)($app['description'] ?? ''))) ?></div>


      <?php if (!empty($appImages)): ?>
        <div class="app-gallery-strip">
          <?php foreach (array_slice($appImages, 0, 4) as $img): ?><img src="<?= htmlspecialchars((string)$img['image_path']) ?>" alt=""><?php endforeach; ?>
          <?php if (count($appImages) > 4): ?><span>+<?= count($appImages) - 4 ?></span><?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="latest-version-box">
        <div class="latest-version-head">
          <div><strong>最新版本</strong><span><?= $latestVersion ? 'v' . htmlspecialchars((string)$latestVersion['version']) : '暂无提交版本' ?></span></div>
          <?php if ($latestVersion): ?><em><?= htmlspecialchars(version_status_label((string)$latestVersion['status'])) ?></em><?php endif; ?>
        </div>
        <?php if ($latestVersion): ?>
          <div class="latest-version-body">
            <p><?= htmlspecialchars((string)($latestVersion['changelog'] ?: '暂无更新说明')) ?></p>
            <?php if (!empty($latestVersion['review_note'])): ?><p class="review-note">审核说明：<?= htmlspecialchars((string)$latestVersion['review_note']) ?></p><?php endif; ?>
            <span><?= htmlspecialchars((string)$latestVersion['created_at']) ?></span>
          </div>
        <?php else: ?>
          <div class="latest-version-body"><p>创建应用后，可在下方提交第一个版本包。</p></div>
        <?php endif; ?>
      </div>

      <?php if ($status === 'hidden'): ?>
        <?php $appeal = $appealMap[(int)$app['id']] ?? null; ?>
        <div class="appeal-box">
          <div><strong>应用已下架</strong><p>如果你认为下架有误，可以提交申诉。申诉通过后应用会自动恢复上架。</p></div>
          <?php if ($appeal && ($appeal['status'] ?? '') === 'pending'): ?>
            <span class="appeal-status">申诉待处理</span>
          <?php else: ?>
            <?php if ($appeal): ?><div class="muted" style="grid-column:1/-1;">最近申诉：<?= ['approved'=>'已通过','rejected'=>'已驳回','pending'=>'待处理'][$appeal['status'] ?? 'pending'] ?? htmlspecialchars((string)$appeal['status']) ?><?= !empty($appeal['review_note']) ? ' · ' . htmlspecialchars((string)$appeal['review_note']) : '' ?></div><?php endif; ?>
            <form method="post" action="/index.php?path=developer" class="appeal-form" data-no-ajax>
              <?= csrf_field() ?><input type="hidden" name="_action" value="appeal_app"><input type="hidden" name="item_id" value="<?= (int)$app['id'] ?>">
              <textarea class="input" name="reason" rows="3" required placeholder="说明申诉理由、整改情况或需要复核的原因"></textarea>
              <button class="btn btn-light" type="submit">提交申诉</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="dev-actions-grid">
        <details class="dev-action-box">
          <summary>编辑资料</summary>
          <form method="post" enctype="multipart/form-data" action="/index.php?path=developer" class="dev-form" style="margin-top:12px;" data-no-ajax>
            <?= csrf_field() ?><input type="hidden" name="_action" value="edit_app"><input type="hidden" name="id" value="<?= (int)$app['id'] ?>">
            <label>应用名称<input class="input" name="name" value="<?= htmlspecialchars($app['name']) ?>" required></label>
            <label>应用分类<select class="select" name="category_id" required><?= market_category_options($appCategories, (int)($app['category_id'] ?? 0), $appType) ?></select></label>
            <?php if (($_SESSION['auth_user']['developer_level'] ?? '') === 'public'): ?><div class="muted public-price-note">公益开发者应用保持免费。</div><?php else: ?><label>价格<input class="input" name="price" type="number" step="0.01" value="<?= htmlspecialchars((string)($app['price'] ?? 0)) ?>"></label><?php endif; ?>
            <label>更换 Logo<input class="input" type="file" name="logo" accept="image/*"></label>
            <label style="grid-column:1/-1;">应用介绍<textarea class="input" name="description" rows="4"><?= htmlspecialchars((string)($app['description'] ?? '')) ?></textarea></label>
            <button class="btn" type="submit">保存资料</button>
          </form>
        </details>
        <details class="dev-action-box">
          <summary>提交新版本</summary>
          <form method="post" enctype="multipart/form-data" action="/index.php?path=developer" style="display:grid;gap:10px;margin-top:12px;" data-no-ajax>
            <?= csrf_field() ?><input type="hidden" name="_action" value="submit_version"><input type="hidden" name="item_id" value="<?= (int)$app['id'] ?>">
            <input class="input" name="version" placeholder="版本号，例如 1.0.1" required>
            <input class="input" type="file" name="package" accept=".zip" required>
            <textarea class="input" name="changelog" rows="3" placeholder="版本更新说明，例如新增功能、修复内容"></textarea>
            <div class="muted">包内 <code>market.json</code> 的 type/slug 必须与该应用一致；提交后会自动把包内版本号改为这里填写的版本号。</div>
            <button class="btn" type="submit">提交版本审核</button>
          </form>
        </details>
        <details class="dev-action-box">
          <summary>展示图片 <span><?= count($appImages) ?></span></summary>
          <form method="post" enctype="multipart/form-data" action="/index.php?path=developer" class="gallery-form" data-no-ajax>
            <?= csrf_field() ?><input type="hidden" name="_action" value="update_images"><input type="hidden" name="item_id" value="<?= (int)$app['id'] ?>">
            <?php if ($appImages): ?><div class="gallery-manage-grid"><?php foreach ($appImages as $img): ?><label><img src="<?= htmlspecialchars((string)$img['image_path']) ?>" alt=""><span><input type="checkbox" name="delete_images[]" value="<?= (int)$img['id'] ?>"> 删除</span></label><?php endforeach; ?></div><?php endif; ?>
            <input class="input" type="file" name="gallery[]" accept="image/*" multiple>
            <div class="muted">可一次选择多张图片上传；不选新图片时，只处理勾选删除。</div>
            <button class="btn btn-light" type="submit">保存展示图片</button>
          </form>
        </details>
        <a class="dev-history-link" href="/index.php?path=developer/history&id=<?= (int)$app['id'] ?>">版本历史 <span><?= $historyCount ?></span></a>
      </div>
    </article>
  <?php endforeach; else: ?><div class="card" style="text-align:center;color:#94a3b8;padding:40px;">还没有应用，先从上方创建一个插件或主题模板。</div><?php endif; ?>
  </section>
</div>
</section>
<script>
(function(){
  const tabs=document.querySelectorAll('.dev-tab'); const panels=document.querySelectorAll('.dev-panel'); const key='clay-dev-tab';
  function activate(name){tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===name)); panels.forEach(p=>p.classList.toggle('active',p.dataset.panel===name)); try{localStorage.setItem(key,name)}catch(e){}}
  tabs.forEach(t=>t.addEventListener('click',()=>activate(t.dataset.tab))); let initial='overview'; try{initial=localStorage.getItem(key)||initial}catch(e){} if(!Array.prototype.some.call(tabs, t=>t.dataset.tab===initial)) initial='overview'; activate(initial);

  function syncCategory(form){
    var type = (form.querySelector('.js-market-type')||{}).value || 'plugin';
    var select = form.querySelector('.js-market-category');
    if(!select) return;
    var html = type === 'theme' ? (select.dataset.themeOptions || '') : (select.dataset.pluginOptions || '');
    select.innerHTML = html;
    var first = select.querySelector('option');
    if(first) select.value = first.value;
  }
  document.querySelectorAll('form.dev-form').forEach(function(form){ syncCategory(form); var type=form.querySelector('.js-market-type'); if(type) type.addEventListener('change', function(){ syncCategory(form); }); });
})();
</script>
<style>
.developer-hero{display:grid;grid-template-columns:minmax(0,1.25fr) 360px;gap:24px;align-items:stretch;padding:30px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 38%,#ecfeff 100%);border-color:#bfdbfe;overflow:hidden}.developer-hero-copy{min-width:0}.developer-hero h1{margin:14px 0 12px;font-size:40px;line-height:1.12;letter-spacing:-.045em;max-width:780px}.developer-hero p{color:#64748b;line-height:1.8;max-width:760px}.developer-hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.developer-hero-card{display:grid;align-content:space-between;gap:18px;border:1px solid #dbeafe;border-radius:22px;background:rgba(255,255,255,.76);box-shadow:0 22px 60px rgba(37,99,235,.12);padding:20px;min-width:0}.hero-card-top span{display:block;color:#64748b;font-size:13px;font-weight:800}.hero-card-top strong{display:block;margin-top:8px;font-size:26px;letter-spacing:-.03em}.hero-metrics{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.hero-metrics div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:12px;min-width:0}.hero-metrics b{display:block;font-size:19px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.hero-metrics span{display:block;margin-top:5px;color:#94a3b8;font-size:12px;font-weight:800}.dev-alert-error{color:#b91c1c;background:#fee2e2;border-color:#fecaca}.upgrade-dev-card{display:flex;justify-content:space-between;gap:14px;align-items:center;background:linear-gradient(135deg,#fff7ed,#fff)}.dev-tabs{display:flex;gap:8px;flex-wrap:wrap;padding:10px}.dev-tab{border:1px solid var(--line);background:#f8fafc;color:#64748b;border-radius:999px;padding:9px 14px;font-weight:900;cursor:pointer}.dev-tab.active,.dev-tab:hover{background:#2563eb;color:#fff;border-color:#2563eb}.dev-panel{display:none}.dev-panel.active{display:block}.dev-stat-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:14px}.dev-stat{min-width:0}.dev-stat span{display:block;color:#64748b;font-size:13px}.dev-stat b{display:block;margin-top:8px;font-size:30px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dev-money-panel{min-width:0;overflow:hidden}.withdraw-form{display:grid;grid-template-columns:120px minmax(140px,1fr) minmax(180px,1fr) auto;gap:10px;margin-top:12px}.dev-main-panel,.dev-app-list{display:grid;gap:18px;min-width:0;max-width:100%;overflow:hidden}.create-app-box summary{display:inline-flex;align-items:center;justify-content:center;background:#2563eb;color:#fff;border-radius:10px;padding:10px 16px;font-size:14px;font-weight:800;cursor:pointer;list-style:none}.create-app-box summary::-webkit-details-marker{display:none}.create-app-box[open] summary{margin-bottom:16px}.section-head h2{margin:0}.section-head p{margin-top:6px;color:#64748b;font-size:13px}.dev-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;margin-top:14px}.dev-form label{display:grid;gap:6px;color:#334155;font-size:13px;font-weight:700;min-width:0}.public-price-note{align-self:end;background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 12px}.dev-form small{color:#94a3b8;font-size:12px;font-weight:500;line-height:1.5}.dev-app-card{min-width:0;max-width:100%;overflow:hidden;border-left:4px solid #e2e8f0}.dev-app-card.app-status-published{border-left-color:#22c55e}.dev-app-card.app-status-pending{border-left-color:#3b82f6}.dev-app-card.app-status-hidden{border-left-color:#f97316}.dev-app-card.app-status-rejected{border-left-color:#ef4444}.dev-app-top{display:grid;grid-template-columns:58px minmax(0,1fr) auto;gap:14px;align-items:start}.app-logo{width:58px;height:58px;border-radius:16px;object-fit:cover;border:1px solid #e2e8f0}.app-logo-fallback{display:grid;place-items:center;background:#eff6ff;color:#2563eb;font-weight:900;font-size:20px}.dev-app-main{min-width:0}.dev-app-tags{display:flex;gap:6px;flex-wrap:wrap}.type-pill,.category-pill,.status-pill{display:inline-flex;border-radius:999px;padding:5px 8px;font-size:12px;font-weight:900}.type-pill{background:#eff6ff;color:#2563eb}.category-pill{background:#f8fafc;color:#64748b}.status-pill{background:#f1f5f9;color:#64748b}.status-pill.published{background:#dcfce7;color:#166534}.status-pill.pending{background:#dbeafe;color:#1d4ed8}.status-pill.hidden{background:#ffedd5;color:#9a3412}.status-pill.rejected{background:#fee2e2;color:#991b1b}.dev-app-card h3{margin:10px 0 6px;font-size:22px;overflow-wrap:anywhere}.app-subline{color:#64748b;font-size:13px;overflow-wrap:anywhere}.dev-app-price{text-align:right;min-width:76px}.dev-app-price strong{display:block}.dev-app-price span{display:block;margin-top:4px;color:#94a3b8;font-size:12px}.app-desc{color:#64748b;line-height:1.7;margin:12px 0 14px;font-size:14px;overflow-wrap:anywhere}.app-gallery-strip{display:flex;gap:8px;overflow-x:auto;overflow-y:hidden;margin:0 0 12px;padding-bottom:6px;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch}.app-gallery-strip img{width:132px;height:84px;flex:0 0 auto;scroll-snap-align:start;border-radius:12px;object-fit:cover;border:1px solid #e2e8f0}.app-gallery-strip span{display:grid;place-items:center;width:84px;height:84px;flex:0 0 auto;border-radius:12px;background:#f8fafc;color:#64748b;font-weight:900}.gallery-form{display:grid;gap:10px;margin-top:12px}.gallery-manage-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(96px,1fr));gap:8px}.gallery-manage-grid label{display:grid;gap:5px;font-size:12px;color:#64748b}.gallery-manage-grid img{width:100%;height:64px;border-radius:10px;object-fit:cover;border:1px solid #e2e8f0}.gallery-manage-grid span{display:flex;gap:5px;align-items:center}.latest-version-box{border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;padding:12px;margin-bottom:12px}.latest-version-head{display:flex;justify-content:space-between;gap:12px;align-items:center}.latest-version-head strong{display:block}.latest-version-head span{display:block;margin-top:3px;color:#2563eb;font-size:13px;font-weight:900}.latest-version-head em{font-style:normal;border-radius:999px;background:#eff6ff;color:#2563eb;padding:5px 8px;font-size:12px;font-weight:900}.latest-version-body p{margin:8px 0 0;color:#334155;line-height:1.6;overflow-wrap:anywhere}.latest-version-body span{display:block;margin-top:8px;color:#94a3b8;font-size:12px}.review-note{color:#64748b!important}.dev-actions-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}.dev-action-box{border:1px solid #e2e8f0;border-radius:14px;padding:12px;background:#fff;min-width:0}.dev-action-box summary{font-weight:900;cursor:pointer;list-style:none;color:#334155}.dev-action-box summary::-webkit-details-marker{display:none}.dev-action-box[open] summary{margin-bottom:10px;color:#2563eb}.dev-history-link{display:flex;align-items:center;justify-content:space-between;gap:10px;border:1px solid #e2e8f0;border-radius:14px;padding:12px;background:#fff;color:#334155;text-decoration:none;font-weight:900}.dev-history-link:hover{border-color:#bfdbfe;color:#2563eb;background:#eff6ff}.dev-history-link span{border-radius:999px;background:#f1f5f9;color:#64748b;padding:2px 7px;font-size:12px}.appeal-box{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:start;margin:10px 0 14px;padding:12px;border:1px solid #fecaca;background:#fff7ed;border-radius:14px}.appeal-box strong{color:#9a3412}.appeal-box p{margin-top:4px;color:#9a3412;font-size:13px;line-height:1.6}.appeal-status{border-radius:999px;padding:6px 9px;background:#ffedd5;color:#9a3412;font-size:12px;font-weight:900}.appeal-form{grid-column:1/-1;display:grid;gap:10px}
@media(max-width:1100px){.developer-hero{grid-template-columns:1fr}.dev-actions-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.withdraw-form{grid-template-columns:1fr 1fr}.withdraw-form .btn{grid-column:1/-1}.dev-app-top{grid-template-columns:58px minmax(0,1fr)}}
@media(max-width:720px){html,body{overflow-x:hidden}.front-content,.dev-main-panel,.dev-app-list,#my-apps,.dev-money-panel,.dev-app-card{width:100%;max-width:100%;min-width:0;overflow-x:hidden}.developer-hero{padding:18px;border-radius:18px}.developer-hero h1{font-size:30px;line-height:1.15;word-break:normal}.developer-hero p{font-size:14px;line-height:1.7;word-break:normal;overflow-wrap:anywhere}.hero-metrics{grid-template-columns:1fr}.upgrade-dev-card{display:grid}.dev-tabs{display:grid;grid-template-columns:1fr 1fr;padding:8px}.dev-tab{padding:9px 10px}.dev-stat-grid{grid-template-columns:1fr 1fr;gap:10px}.dev-stat{padding:14px!important}.dev-stat b{font-size:22px}.withdraw-form{grid-template-columns:1fr}.dev-form{grid-template-columns:1fr}.dev-actions-grid{grid-template-columns:1fr}.dev-app-card{padding:14px!important}.dev-app-top{grid-template-columns:46px minmax(0,1fr);gap:10px}.app-logo{width:46px;height:46px;border-radius:14px}.dev-app-price{grid-column:1/-1;text-align:left;display:flex;gap:10px;align-items:baseline}.latest-version-head{align-items:flex-start}.appeal-box{grid-template-columns:1fr}.table-wrap{max-width:100%;overflow-x:auto}.muted,.app-subline,.app-desc{word-break:normal;overflow-wrap:anywhere}}
@media(max-width:420px){.dev-stat-grid{grid-template-columns:1fr}.developer-hero h1{font-size:28px}.dev-stat b{font-size:24px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
