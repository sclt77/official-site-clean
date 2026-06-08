<?php $pageTitle='订单支付'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card market-pay-hero">
  <div>
    <span class="badge">Market Checkout</span>
    <h1>应用授权订单支付</h1>
    <p>付费应用支付成功后会自动生成授权 Key。随后可到“我的购买”绑定域名，并复制到 ClayBBS 论坛后台完成安装。</p>
  </div>
  <div class="pay-summary">
    <span>支付金额</span>
    <strong>￥<?= htmlspecialchars(number_format((float)$order['amount'], 2, '.', '')) ?></strong>
    <small>订单号：<code><?= htmlspecialchars((string)$order['order_no']) ?></code></small>
  </div>
</section>

<div class="card pay-card">
  <div>
    <h2><?= htmlspecialchars((string)($item['name'] ?? $order['name'] ?? '应用授权')) ?></h2>
    <div class="muted">类型：<?= ($item['type'] ?? $order['type'] ?? '') === 'theme' ? '主题' : '插件' ?> / v<?= htmlspecialchars((string)($item['version'] ?? $order['version'] ?? '')) ?></div>
  </div>
  <a class="btn btn-light" href="/index.php?path=market/detail&id=<?= (int)$order['item_id'] ?>">返回应用详情</a>
</div>

<?php if (($order['status'] ?? '') === 'paid'): ?>
  <div class="card pay-success">
    <strong>支付已完成，授权 Key 已生成。</strong>
    <?php if (!empty($license)): ?><div class="license-key"><code><?= htmlspecialchars((string)$license['license_key']) ?></code></div><?php endif; ?>
  </div>
  <a class="btn" href="/index.php?path=me/purchases">去我的购买</a>
<?php else: ?>
  <?php if (!empty($payError)): ?>
    <div class="card pay-error"><?= htmlspecialchars($payError) ?></div>
    <a class="btn btn-light" href="/index.php?path=market/detail&id=<?= (int)$order['item_id'] ?>">返回应用详情</a>
  <?php else: ?>
    <div class="card pay-qr-card">
      <div><h2>支付宝扫码支付</h2><p class="muted">请使用支付宝扫描下方二维码完成支付。支付完成后如果页面未自动更新，可点击刷新状态。</p></div>
      <?php if (!empty($qrCode)): ?>
        <div class="qr-box"><img alt="支付宝支付二维码" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($qrCode) ?>"><code><?= htmlspecialchars($qrCode) ?></code></div>
      <?php else: ?>
        <div class="muted">支付宝未返回二维码，请稍后重试。</div>
      <?php endif; ?>
      <a class="btn btn-light" href="/index.php?path=market/pay&order_no=<?= urlencode((string)$order['order_no']) ?>&check=1">我已支付，刷新状态</a>
    </div>
  <?php endif; ?>
<?php endif; ?>

<style>
.market-pay-hero{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:22px;align-items:stretch;padding:30px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.market-pay-hero h1{margin:14px 0 10px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.market-pay-hero p{color:#64748b;line-height:1.8;max-width:760px}.pay-summary{display:grid;align-content:center;gap:9px;border:1px solid #dbeafe;border-radius:22px;background:rgba(255,255,255,.78);box-shadow:0 20px 56px rgba(37,99,235,.12);padding:20px;min-width:0}.pay-summary span{color:#64748b;font-weight:800;font-size:13px}.pay-summary strong{font-size:34px;color:#dc2626;letter-spacing:-.04em}.pay-summary small{color:#64748b;line-height:1.6;overflow-wrap:anywhere}.pay-card{display:flex;align-items:center;justify-content:space-between;gap:16px}.pay-card h2{font-size:22px}.pay-success{background:#dcfce7;color:#166534;border-color:#bbf7d0}.pay-error{background:#fee2e2;color:#b91c1c;border-color:#fecaca}.license-key{margin-top:10px}.license-key code{display:block;word-break:break-all;background:rgba(255,255,255,.58);border-radius:12px;padding:10px 12px}.pay-qr-card{display:grid;gap:14px}.qr-box{display:flex;align-items:center;gap:14px;flex-wrap:wrap}.qr-box img{width:220px;height:220px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:8px}.qr-box code{display:block;max-width:520px;word-break:break-all;color:#64748b;line-height:1.7}@media(max-width:720px){.market-pay-hero{grid-template-columns:1fr;padding:20px}.market-pay-hero h1{font-size:30px}.pay-summary strong{font-size:28px}.pay-card{display:grid}.qr-box img{width:190px;height:190px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
