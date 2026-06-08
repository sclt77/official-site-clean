<?php $pageTitle='我的购买'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card account-hero">
  <div><span class="badge">Purchases</span><h1>我的购买</h1><p>这里显示你已获取或购买的插件和主题授权 Key。订单流水请到“订单中心”查看。</p></div>
  <div class="account-hero-stat"><span>授权数量</span><strong><?= count($licenses ?? []) ?></strong><small>插件 / 主题授权</small></div>
</section>
<div class="card purchase-card">
  <div class="table-wrap"><table class="table"><thead><tr><th>应用</th><th>类型</th><th>版本</th><th>授权 Key</th><th>绑定域名</th><th>操作</th></tr></thead><tbody>
  <?php foreach (($licenses ?? []) as $l): ?>
    <tr><td><?= htmlspecialchars($l['name']) ?><div class="muted"><?= htmlspecialchars($l['slug']) ?></div></td><td><?= $l['type']==='theme'?'主题':'插件' ?></td><td><?= htmlspecialchars($l['version']) ?></td><td class="break-all"><code><?= htmlspecialchars($l['license_key']) ?></code></td><td><?= $l['bound_domain'] ? htmlspecialchars($l['bound_domain']) : ((float)$l['price']>0?'未绑定':'免费无需绑定') ?></td><td>
      <?php if ((float)$l['price'] > 0): ?>
        <?php if ($l['bound_domain']): ?><form method="post" action="/index.php?path=me/purchases"><?= csrf_field() ?><input type="hidden" name="_action" value="unbind"><input type="hidden" name="license_key" value="<?= htmlspecialchars($l['license_key']) ?>"><button class="btn btn-light">解绑</button></form><?php else: ?><form method="post" action="/index.php?path=me/purchases" class="purchase-bind-form"><?= csrf_field() ?><input type="hidden" name="_action" value="bind"><input type="hidden" name="license_key" value="<?= htmlspecialchars($l['license_key']) ?>"><input class="input" name="domain" placeholder="绑定域名"><button class="btn">绑定</button></form><?php endif; ?>
      <?php else: ?><span class="muted">复制 Key 到论坛后台安装</span><?php endif; ?>
    </td></tr>
  <?php endforeach; ?>
  <?php if (empty($licenses)): ?><tr><td colspan="6" style="text-align:center;color:#94a3b8;padding:32px;">还没有获取任何应用</td></tr><?php endif; ?>
  </tbody></table></div>
</div>
<style>.account-hero{display:grid;grid-template-columns:minmax(0,1fr) 240px;gap:20px;align-items:stretch;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.account-hero h1{margin:14px 0 10px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.account-hero p{color:#64748b;line-height:1.8}.account-hero-stat{display:grid;align-content:center;gap:8px;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.78);box-shadow:0 18px 50px rgba(37,99,235,.10);padding:18px}.account-hero-stat span,.account-hero-stat small{color:#64748b;font-size:13px;font-weight:800}.account-hero-stat strong{font-size:34px;letter-spacing:-.04em}.purchase-card{overflow:hidden}.purchase-bind-form{display:flex;gap:8px;flex-wrap:wrap}.purchase-bind-form .input{width:180px}@media(max-width:720px){.account-hero{grid-template-columns:1fr;padding:20px}.account-hero h1{font-size:30px}.purchase-bind-form .input{width:100%}}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
