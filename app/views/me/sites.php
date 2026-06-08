<?php
$pageTitle='我的授权';
$product = in_array(($product ?? ($_GET['product'] ?? 'claybbs')), ['claybbs','cutot'], true) ? ($product ?? ($_GET['product'] ?? 'claybbs')) : 'claybbs';
$productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
$tabRaw = (string)($_GET['tab'] ?? '');
$allowedTabs = ['bind', 'apply', 'buy', 'sites'];
$tab = in_array($tabRaw, $allowedTabs, true) ? $tabRaw : 'bind';
if (empty($siteLimitRequestEnabled) && $tab === 'apply') {
    $tab = 'bind';
}
if (empty($authPurchaseEnabled) && $tab === 'buy') {
    $tab = 'bind';
}
require dirname(__DIR__) . '/layouts/main.php';
?>
<section class="card account-hero">
  <div><span class="badge">License Sites</span><h1>我的授权</h1><p>当前产品：<?= htmlspecialchars($productLabel) ?>。ClayBBS 与 CUTOT 授权分开绑定，生成各自的 site_id / token / license_key。</p></div>
  <div class="account-hero-stat"><span>绑定名额</span><strong><?= (int)($siteCount ?? 0) ?> / <?= (int)($siteLimit ?? 0) ?></strong><small><?php if (!empty($unbindEnabled)): ?>允许自助解除绑定<?php else: ?>解除绑定请联系管理员<?php endif; ?></small></div>
</section>
<?php if (!empty($error)): ?><div class="card" style="background:#fee2e2;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="card" style="background:#dcfce7;color:#166534;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card auth-tabs-card product-auth-tabs-card">
  <div class="auth-tabs" role="tablist" aria-label="授权产品切换">
    <a class="auth-tab <?= $product === 'claybbs' ? 'active' : '' ?>" href="/index.php?path=me/sites&product=claybbs&tab=bind">ClayBBS 授权</a>
    <a class="auth-tab <?= $product === 'cutot' ? 'active' : '' ?>" href="/index.php?path=me/sites&product=cutot&tab=bind">CUTOT 授权</a>
  </div>
</div>

<div class="card auth-tabs-card">
  <div class="auth-tabs" role="tablist" aria-label="我的授权功能切换">
    <a class="auth-tab <?= $tab === 'bind' ? 'active' : '' ?>" href="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=bind">绑定新站点</a>
    <?php if (!empty($siteLimitRequestEnabled)): ?>
      <a class="auth-tab <?= $tab === 'apply' ? 'active' : '' ?>" href="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=apply">申请授权<?php if (!empty($pendingSiteLimitRequest)): ?><span>待审</span><?php endif; ?></a>
    <?php endif; ?>
    <?php if (!empty($authPurchaseEnabled)): ?>
      <a class="auth-tab <?= $tab === 'buy' ? 'active' : '' ?>" href="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=buy">购买授权</a>
    <?php endif; ?>
    <a class="auth-tab <?= $tab === 'sites' ? 'active' : '' ?>" href="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=sites">我的站点 <em><?= (int)($siteCount ?? 0) ?></em></a>
  </div>
</div>

<?php if ($tab === 'bind'): ?>
<div class="card">
  <h3>绑定 <?= htmlspecialchars($productLabel) ?> 新站点</h3>
  <?php if ((int)($siteCount ?? 0) >= (int)($siteLimit ?? 0)): ?>
    <div class="muted">绑定名额已用完。<?php if (!empty($siteLimitRequestEnabled)): ?>你可以切换到“申请授权”提交免费审核。<?php elseif (!empty($authPurchaseEnabled)): ?>你可以切换到“购买授权”增加绑定名额。<?php else: ?>如需绑定更多域名，请联系管理员调整绑定数量。<?php endif; ?></div>
  <?php else: ?>
  <form method="post" action="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=bind" class="grid auth-form-grid">
    <input type="hidden" name="_action" value="bind"><input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
    <?= csrf_field() ?>
    <div><label>域名</label><input class="input" name="domain" placeholder="example.com" required></div>
    <div><label>邮箱</label><input class="input" name="email" required></div>
    <div><button class="btn" type="submit" style="width:100%;">绑定并生成凭据</button></div>
  </form>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'apply' && !empty($siteLimitRequestEnabled)): ?>
<div class="card">
  <h3>申请 <?= htmlspecialchars($productLabel) ?> 授权名额</h3>
  <div class="muted" style="margin-bottom:12px;">无需付款，提交站点用途和申请原因后由管理员审核；通过后会增加你的可绑定站点数量。</div>
  <?php if (!empty($latestSiteLimitRequest)): ?>
    <?php $requestStatus = (string)($latestSiteLimitRequest['status'] ?? ''); ?>
    <div class="site-request-result <?= $requestStatus === 'rejected' ? 'rejected' : ($requestStatus === 'approved' ? 'approved' : '') ?>">
      <strong>最近申请状态：<?= htmlspecialchars(['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝'][$requestStatus] ?? $requestStatus) ?></strong>
      <span>申请增加 <?= (int)($latestSiteLimitRequest['requested_count'] ?? 0) ?> 个名额，提交时间 <?= htmlspecialchars((string)($latestSiteLimitRequest['created_at'] ?? '')) ?></span>
      <?php if (!empty($latestSiteLimitRequest['review_note'])): ?><br><span>审核备注：<?= nl2br(htmlspecialchars((string)$latestSiteLimitRequest['review_note'])) ?></span><?php endif; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($pendingSiteLimitRequest)): ?>
    <div class="muted">你已有待审核申请，处理完成前不能重复提交。</div>
  <?php elseif (!empty($approvedSiteLimitRequest)): ?>
    <div class="muted">你已经有通过的授权申请，若仍需增加名额请联系管理员或使用购买授权。</div>
  <?php else: ?>
    <form method="post" action="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=apply" class="grid auth-request-grid">
      <?= csrf_field() ?><input type="hidden" name="_action" value="request_limit"><input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
      <div><label>申请增加数量</label><input class="input" type="number" name="requested_count" min="1" max="<?= (int)$siteLimitRequestMax ?>" value="1" required></div>
      <div><label>当前绑定情况</label><input class="input" value="<?= (int)($siteCount ?? 0) ?> / <?= (int)($siteLimit ?? 0) ?>" readonly></div>
      <div class="full-row"><label>申请原因</label><textarea class="input" name="reason" rows="4" maxlength="800" placeholder="请说明站点用途、预计绑定域名数量和使用场景" required></textarea></div>
      <div><button class="btn" type="submit" style="width:100%;">提交申请</button></div>
    </form>
    <div class="muted" style="margin-top:8px;">单次最多可申请 <?= (int)$siteLimitRequestMax ?> 个名额。</div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'buy' && !empty($authPurchaseEnabled)): ?>
<div class="card">
  <h3>购买 <?= htmlspecialchars($productLabel) ?> 授权名额</h3>
  <div class="muted" style="margin-bottom:12px;">当前单价 ￥<?= htmlspecialchars(number_format((float)($authPurchasePrice ?? 0), 2, '.', '')) ?> / 个，支付成功后自动增加可绑定站点数量。</div>
  <?php if ((float)($authPurchasePrice ?? 0) <= 0): ?>
    <div class="muted">后台尚未配置授权购买价格，请联系管理员。</div>
  <?php else: ?>
    <form method="post" action="/index.php?path=me/sites&product=<?= urlencode($product) ?>&tab=buy" class="grid auth-request-grid" data-no-ajax>
      <?= csrf_field() ?><input type="hidden" name="_action" value="purchase_limit"><input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
      <div><label>购买数量</label><input class="input" type="number" name="requested_count" min="1" max="<?= (int)$authPurchaseMax ?>" value="1" required></div>
      <div><label>预计金额</label><input class="input" value="￥<?= htmlspecialchars(number_format((float)($authPurchasePrice ?? 0), 2, '.', '')) ?> × 数量" readonly></div>
      <div><button class="btn" type="submit" style="width:100%;">去支付</button></div>
    </form>
    <div class="muted" style="margin-top:8px;">单次最多可购买 <?= (int)$authPurchaseMax ?> 个名额。</div>
  <?php endif; ?>
  <?php if (!empty($siteLimitOrders)): ?>
    <div class="table-wrap" style="margin-top:14px;"><table class="table"><thead><tr><th>订单号</th><th>数量</th><th>金额</th><th>状态</th><th>时间</th><th>操作</th></tr></thead><tbody>
      <?php foreach (array_slice($siteLimitOrders, 0, 8) as $order): ?><tr><td class="break-all"><code><?= htmlspecialchars((string)$order['order_no']) ?></code></td><td><?= (int)$order['requested_count'] ?></td><td>￥<?= htmlspecialchars(number_format((float)$order['amount'], 2, '.', '')) ?></td><td><?= ['pending'=>'待支付','paid'=>'已支付','closed'=>'已关闭'][$order['status']] ?? htmlspecialchars((string)$order['status']) ?></td><td><?= htmlspecialchars((string)$order['created_at']) ?></td><td><?php if (($order['status'] ?? '') === 'pending'): ?><a class="btn btn-light" href="/index.php?path=me/site-limit-pay&order_no=<?= urlencode((string)$order['order_no']) ?>">继续支付</a><?php else: ?><span class="muted">已完成</span><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($tab === 'sites'): ?>
<div class="card">
  <h3>我的站点</h3>
  <?php if (!empty($sites)): ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>产品</th><th>域名</th><th>邮箱</th><th>Site ID</th><th>Token</th><th>授权码</th><th>clayguard.lic</th><th>状态</th><th>最后活跃</th><?php if (!empty($unbindEnabled)): ?><th>操作</th><?php endif; ?></tr></thead><tbody>
    <?php foreach ($sites as $s): ?><tr><td><?= htmlspecialchars(strtoupper((string)($s['product'] ?? $product))) ?></td><td class="break-all"><?= htmlspecialchars($s['domain']) ?></td><td class="break-all"><?= htmlspecialchars($s['email']) ?></td><td class="break-all"><?= htmlspecialchars($s['site_id']) ?></td><td class="break-all" style="font-size:12px;max-width:260px;"><?= htmlspecialchars($s['token']) ?></td><td class="break-all" style="font-size:12px;max-width:180px;"><?= htmlspecialchars((string)($s['license_key'] ?? '')) ?></td><td><a class="btn btn-light" href="/index.php?path=me/clayguard-license&id=<?= (int)$s['id'] ?>">下载 clayguard.lic</a></td><td><?= htmlspecialchars($s['status']) ?></td><td class="break-all"><?= htmlspecialchars((string)($s['last_seen_at'] ?? '')) ?></td><?php if (!empty($unbindEnabled)): ?><td><form method="post" action="/index.php?path=me/sites&tab=sites" onsubmit="return confirm('确认解除该授权绑定？解除后该站点的 site_id / token / license_key 将立即失效。');"><?= csrf_field() ?><input type="hidden" name="_action" value="unbind"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-light" type="submit" style="color:#dc2626;">解除绑定</button></form></td><?php endif; ?></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php else: ?><div class="muted">暂无站点</div><?php endif; ?>
  <p class="sites-mobile-hint">如果表格横向滚动，请左右滑动查看完整授权信息。</p>
</div>
<?php endif; ?>

<style>
.account-hero{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:20px;align-items:stretch}.account-hero h1{margin:14px 0 10px}.account-hero p{color:var(--muted);line-height:1.75}.account-hero-stat{display:grid;align-content:center;gap:8px}.account-hero-stat span,.account-hero-stat small{color:var(--muted);font-size:13px;font-weight:700}.account-hero-stat strong{font-size:30px;font-weight:300;letter-spacing:-.4px}.auth-tabs-card{padding:10px!important}.product-auth-tabs-card{margin-bottom:0!important}.auth-tabs{display:flex;gap:8px;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:2px}.auth-tab{flex:0 0 auto;white-space:nowrap}.auth-tab span,.auth-tab em{font-style:normal;display:inline-flex;min-width:20px;height:20px;padding:0 6px;border-radius:var(--radius-sm);align-items:center;justify-content:center;font-size:11px;font-weight:800}.auth-tab span{background:#ef4444;color:#fff}.auth-tab em{background:rgba(255,255,255,.22);color:inherit}.site-request-result{margin:10px 0 14px;padding:12px 14px;border-radius:var(--radius);font-size:13px;line-height:1.7}.site-request-result strong{display:block;margin-bottom:4px}.site-request-result.rejected{background:#fef2f2;color:#991b1b;border:1px solid #fecaca}.site-request-result.approved{background:#f0fdf4;color:#166534;border:1px solid #bbf7d0}.auth-form-grid{grid-template-columns:repeat(3,minmax(0,1fr));align-items:end;gap:12px}.auth-request-grid{grid-template-columns:160px minmax(0,1fr) auto;align-items:end;gap:12px}.sites-mobile-hint{display:none}@media(max-width:900px){.account-hero{display:block!important}.auth-form-grid,.auth-request-grid{grid-template-columns:1fr!important}.sites-mobile-hint{display:block;color:var(--muted);font-size:12px;margin:6px 0 10px}.auth-tabs-card{padding:8px!important}}
</style>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
