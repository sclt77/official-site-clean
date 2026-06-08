<?php $pageTitle='加入开发者'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="developer-join-hero card">
  <div>
    <span class="badge">Developer Program</span>
    <h1>把插件和主题发布到 ClayBBS 官方生态</h1>
    <p>开发者计划提供应用创建、版本审核、市场分发、订单分成与提现链路。你可以先以公益开发者身份发布免费作品，也可以购买普通开发者权限发布商业应用。</p>
  </div>
  <div class="join-hero-panel">
    <strong>生态能力</strong>
    <span>官方市场展示</span>
    <span>版本审核与分发</span>
    <span>销售分成与提现</span>
  </div>
</section>
<div class="join-options">
  <div class="card join-option">
    <div class="join-option-head"><span>Free</span><h2>公益开发者</h2></div>
    <div class="join-price free">免费申请</div>
    <p>适合发布免费插件和免费主题，参与社区共建。申请通过后即可使用开发者中心。</p>
    <?php if (!empty($application) && ($application['status'] ?? '') === 'pending'): ?>
      <div class="notice pending">你的公益开发者申请正在审核中，请耐心等待。</div>
    <?php elseif (!empty($application) && ($application['status'] ?? '') === 'approved'): ?>
      <div class="notice ok">你的公益开发者申请已通过，请刷新或重新登录后进入开发者中心。</div>
    <?php else: ?>
      <?php if (!empty($application) && ($application['status'] ?? '') === 'rejected'): ?><div class="notice rejected">上次申请未通过：<?= htmlspecialchars((string)($application['review_note'] ?? '')) ?></div><?php endif; ?>
      <form method="post" action="/index.php?path=developer" class="join-form">
        <?= csrf_field() ?><input type="hidden" name="join_type" value="public">
        <textarea class="input" name="reason" rows="3" placeholder="简单介绍你想发布的免费插件/主题，可留空"></textarea>
        <button class="btn btn-light" type="submit">提交公益开发者申请</button>
      </form>
    <?php endif; ?>
  </div>
  <div class="card join-option featured">
    <div class="join-option-head"><span>Commercial</span><h2>普通开发者</h2></div>
    <div class="join-price">￥<?= htmlspecialchars(number_format((float)($settings['developer_join_price'] ?? 99), 2, '.', '')) ?></div>
    <p>适合发布商业插件和主题，并获得应用销售收益。</p>
    <ul><li>可发布免费或付费应用</li><li>可查看销售订单</li><li>可申请提现</li></ul>
    <form method="post" action="/index.php?path=developer" data-no-ajax><?= csrf_field() ?><input type="hidden" name="join_type" value="paid"><button class="btn" type="submit">购买普通开发者权限</button></form>
  </div>
</div>
<style>
.developer-join-hero{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:22px;align-items:stretch;padding:30px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.developer-join-hero h1{margin:14px 0 12px;font-size:40px;line-height:1.12;letter-spacing:-.045em}.developer-join-hero p{color:#64748b;line-height:1.8;max-width:760px}.join-hero-panel{display:grid;align-content:center;gap:10px;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.78);padding:18px;box-shadow:0 18px 50px rgba(37,99,235,.10)}.join-hero-panel strong{font-size:18px}.join-hero-panel span{display:block;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;padding:10px 12px;color:#475569;font-weight:800;font-size:13px}.join-options{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.join-option{display:grid;gap:12px}.join-option.featured{border-color:#bfdbfe;background:linear-gradient(135deg,#eff6ff,#fff)}.join-option-head span{display:inline-flex;border-radius:999px;background:#eff6ff;color:#2563eb;padding:5px 8px;font-size:12px;font-weight:900}.join-option-head h2{margin-top:10px}.join-price{font-size:34px;font-weight:900;color:#dc2626;letter-spacing:-.03em}.join-price.free{color:#16a34a}.join-option p,.join-option li{color:#64748b;line-height:1.7}.join-option ul{padding-left:18px}.join-form{display:grid;gap:10px}.notice{border-radius:12px;padding:10px 12px;font-weight:700}.notice.pending{background:#fef3c7;color:#92400e}.notice.ok{background:#dcfce7;color:#166534}.notice.rejected{background:#fee2e2;color:#991b1b}@media(max-width:820px){.developer-join-hero{grid-template-columns:1fr;padding:20px}.developer-join-hero h1{font-size:30px}.join-options{grid-template-columns:1fr}.join-price{font-size:28px}}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
