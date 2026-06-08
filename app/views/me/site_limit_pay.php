<?php $pageTitle='购买授权名额'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card pay-hero">
  <div>
    <span class="badge">License Purchase</span>
    <h1>购买授权名额</h1>
    <p>支付完成后，系统会自动增加你的可绑定站点授权名额。</p>
    <div class="muted">订单号：<code><?= htmlspecialchars((string)$order['order_no']) ?></code></div>
  </div>
  <div class="pay-amount-box"><span>购买数量</span><strong><?= (int)($order['requested_count'] ?? 1) ?> 个</strong><small>应付 ￥<?= htmlspecialchars(number_format((float)$order['amount'], 2, '.', '')) ?></small></div>
</section>
<?php if (($order['status'] ?? '') === 'paid'): ?>
  <div class="card pay-success">支付已完成，授权名额已增加。</div><a class="btn" href="/index.php?path=me/sites">返回我的授权</a>
<?php else: ?>
  <?php if (!empty($payError)): ?><div class="card pay-error"><?= htmlspecialchars($payError) ?></div><?php endif; ?>
  <?php if (!empty($qrCode)): ?>
    <div class="card qr-card">
      <div><h2>支付宝扫码支付</h2><p class="muted">支付完成后点击下方按钮刷新状态。</p></div>
      <div class="qr-box"><img alt="支付宝支付二维码" src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= urlencode($qrCode) ?>"><code><?= htmlspecialchars($qrCode) ?></code></div>
      <a class="btn btn-light" href="/index.php?path=me/site-limit-pay&order_no=<?= urlencode((string)$order['order_no']) ?>&check=1">我已支付，刷新状态</a>
    </div>
  <?php endif; ?>
<?php endif; ?>
<style>
.pay-hero{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:20px;align-items:stretch;padding:30px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.pay-hero h1{margin:14px 0 10px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.pay-hero p{color:#64748b;line-height:1.8;margin-bottom:14px}.pay-amount-box{display:grid;align-content:center;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.8);padding:20px;box-shadow:0 18px 50px rgba(37,99,235,.10)}.pay-amount-box span,.pay-amount-box small{color:#64748b;font-weight:800;font-size:13px}.pay-amount-box strong{display:block;margin-top:8px;font-size:34px;color:#2563eb;letter-spacing:-.04em}.pay-success{background:#dcfce7;color:#166534;border-color:#bbf7d0}.pay-error{background:#fee2e2;color:#b91c1c;border-color:#fecaca}.qr-card{display:grid;gap:14px}.qr-box{display:flex;align-items:center;gap:14px;flex-wrap:wrap}.qr-box img{width:220px;height:220px;border:1px solid #e2e8f0;border-radius:14px;background:#fff;padding:8px}.qr-box code{word-break:break-all;color:#64748b;line-height:1.7}@media(max-width:720px){.pay-hero{grid-template-columns:1fr;padding:20px}.pay-hero h1{font-size:30px}.pay-amount-box strong{font-size:28px}.qr-box img{width:190px;height:190px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
