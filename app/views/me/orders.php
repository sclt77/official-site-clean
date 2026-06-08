<?php $pageTitle='订单中心'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class="page-shell">
<section class="card account-hero">
  <div><span class="badge">Orders</span><h1>订单中心</h1><p>这里集中展示你的付款订单。已购买应用的授权 Key 请到“我的购买”查看。</p></div>
  <div class="account-hero-stat"><span>订单总数</span><strong><?= count($marketOrders ?? []) + count($developerOrders ?? []) ?></strong><small>应用订单 / 开发者开通订单</small></div>
</section>

<div class="card order-tabs-card">
  <div class="order-tabs"><button class="order-tab active" data-tab="market" type="button">应用订单</button><button class="order-tab" data-tab="developer" type="button">开发者开通订单</button></div>
  <section class="order-panel active" data-panel="market">
    <div class="table-wrap"><table class="table"><thead><tr><th>订单号</th><th>应用</th><th>金额</th><th>状态</th><th>支付宝交易号</th><th>时间</th><th>操作</th></tr></thead><tbody>
    <?php foreach (($marketOrders ?? []) as $o): ?>
      <tr><td class="break-all"><code><?= htmlspecialchars((string)$o['order_no']) ?></code></td><td><?= htmlspecialchars((string)$o['name']) ?><div class="muted"><?= ($o['type'] ?? '')==='theme'?'主题':'插件' ?> / <?= htmlspecialchars((string)$o['slug']) ?></div></td><td>￥<?= htmlspecialchars(number_format((float)$o['amount'],2,'.','')) ?></td><td><span class="order-status <?= htmlspecialchars((string)$o['status']) ?>"><?= ['pending'=>'待支付','paid'=>'已支付','closed'=>'已关闭'][$o['status'] ?? 'pending'] ?? htmlspecialchars((string)$o['status']) ?></span></td><td class="break-all"><?= htmlspecialchars((string)($o['trade_no'] ?? '')) ?></td><td><?= htmlspecialchars((string)$o['created_at']) ?><?php if (!empty($o['paid_at'])): ?><div class="muted">支付：<?= htmlspecialchars((string)$o['paid_at']) ?></div><?php endif; ?></td><td><?php if (($o['status'] ?? '')==='pending'): ?><a class="btn btn-light" href="/index.php?path=market/pay&order_no=<?= urlencode((string)$o['order_no']) ?>">继续支付</a><?php elseif (($o['status'] ?? '')==='paid'): ?><a class="btn btn-light" href="/index.php?path=me/purchases">查看授权</a><?php else: ?><span class="muted">无</span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($marketOrders)): ?><tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:28px;">暂无应用订单</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>
  <section class="order-panel" data-panel="developer">
    <div class="table-wrap"><table class="table"><thead><tr><th>订单号</th><th>类型</th><th>金额</th><th>状态</th><th>支付宝交易号</th><th>时间</th><th>操作</th></tr></thead><tbody>
    <?php foreach (($developerOrders ?? []) as $o): ?>
      <tr><td class="break-all"><code><?= htmlspecialchars((string)$o['order_no']) ?></code></td><td>普通开发者权限</td><td>￥<?= htmlspecialchars(number_format((float)$o['amount'],2,'.','')) ?></td><td><span class="order-status <?= htmlspecialchars((string)$o['status']) ?>"><?= ['pending'=>'待支付','paid'=>'已支付','closed'=>'已关闭'][$o['status'] ?? 'pending'] ?? htmlspecialchars((string)$o['status']) ?></span></td><td class="break-all"><?= htmlspecialchars((string)($o['trade_no'] ?? '')) ?></td><td><?= htmlspecialchars((string)$o['created_at']) ?><?php if (!empty($o['paid_at'])): ?><div class="muted">支付：<?= htmlspecialchars((string)$o['paid_at']) ?></div><?php endif; ?></td><td><?php if (($o['status'] ?? '')==='pending'): ?><a class="btn btn-light" href="/index.php?path=developer/join-pay&order_no=<?= urlencode((string)$o['order_no']) ?>">继续支付</a><?php elseif (($o['status'] ?? '')==='paid'): ?><a class="btn btn-light" href="/index.php?path=developer">进入开发者中心</a><?php else: ?><span class="muted">无</span><?php endif; ?></td></tr>
    <?php endforeach; ?>
    <?php if (empty($developerOrders)): ?><tr><td colspan="7" style="text-align:center;color:#94a3b8;padding:28px;">暂无开发者开通订单</td></tr><?php endif; ?>
    </tbody></table></div>
  </section>
</div>

<style>
.account-hero{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:20px;align-items:stretch}.account-hero h1{margin:14px 0 10px}.account-hero p{color:var(--muted);line-height:1.75}.account-hero-stat{display:grid;align-content:center;gap:8px}.account-hero-stat span,.account-hero-stat small{color:var(--muted);font-size:13px;font-weight:700}.account-hero-stat strong{font-size:34px;font-weight:300;letter-spacing:-.4px}.order-tabs{display:flex;gap:8px;flex-wrap:wrap;border-bottom:1px solid var(--line);padding-bottom:12px;margin-bottom:14px}.order-panel{display:none}.order-panel.active{display:block}.order-status.pending{background:#fef3c7;color:#92400e;border-color:#fde68a}.order-status.closed{background:#fee2e2;color:#991b1b;border-color:#fecaca}@media(max-width:720px){.account-hero{display:block!important}.order-tabs{flex-wrap:nowrap;overflow-x:auto}.order-tab{flex:0 0 auto;width:auto!important}}
</style>
<script>(function(){const tabs=document.querySelectorAll('.order-tab');const panels=document.querySelectorAll('.order-panel');const key='clay-me-orders-tab';function active(n){tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===n));panels.forEach(p=>p.classList.toggle('active',p.dataset.panel===n));try{localStorage.setItem(key,n)}catch(e){}}tabs.forEach(t=>t.addEventListener('click',()=>active(t.dataset.tab)));let n='market';try{n=localStorage.getItem(key)||n}catch(e){}if(!document.querySelector('.order-tab[data-tab="'+n+'"]'))n='market';active(n);})();</script>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
