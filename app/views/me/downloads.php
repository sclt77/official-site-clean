<?php $pageTitle='我的下载'; $product = in_array(($product ?? ($_GET['product'] ?? 'claybbs')), ['claybbs','cutot'], true) ? ($product ?? ($_GET['product'] ?? 'claybbs')) : 'claybbs'; $productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card account-hero">
  <div><span class="badge">Downloads</span><h1>我的下载</h1><p>这里只展示当前账号的 <?= htmlspecialchars($productLabel) ?> 下载历史，不混入其他产品、授权、订单或个人资料。</p></div>
  <div class="account-hero-stat"><span>下载记录</span><strong><?= count($downloads ?? []) ?></strong></div>
</section>
<div class="card"><div class="product-tabs" style="display:flex;gap:10px;flex-wrap:wrap"><a class="auth-tab <?= $product === 'claybbs' ? 'active' : '' ?>" href="/index.php?path=me/downloads&product=claybbs">ClayBBS 下载</a><a class="auth-tab <?= $product === 'cutot' ? 'active' : '' ?>" href="/index.php?path=me/downloads&product=cutot">CUTOT 下载</a></div></div>
<div class="card account-table-card"><div class="table-wrap"><table class="table"><thead><tr><th>时间</th><th>类型</th><th>版本</th><th>文件</th></tr></thead><tbody>
<?php foreach ($downloads as $d): ?><tr><td class="break-all"><?= htmlspecialchars($d['created_at']) ?></td><td><?= htmlspecialchars((string)($d['kind'] ?? $d['type'])) ?></td><td><?= htmlspecialchars((string)($d['version'] ?? '')) ?></td><td class="break-all"><?= htmlspecialchars((string)($d['filename'] ?? '')) ?></td></tr><?php endforeach; ?>
<?php if (empty($downloads)): ?><tr><td colspan="4" style="text-align:center;color:#94a3b8;padding:32px;">暂无下载记录</td></tr><?php endif; ?>
</tbody></table></div></div>
<style>.account-hero{display:grid;grid-template-columns:minmax(0,1fr) 220px;gap:20px;align-items:stretch;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.account-hero h1{margin:14px 0 10px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.account-hero p{color:#64748b;line-height:1.8}.account-hero-stat{display:grid;align-content:center;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.78);box-shadow:0 18px 50px rgba(37,99,235,.10);padding:18px}.account-hero-stat span{color:#64748b;font-size:13px;font-weight:800}.account-hero-stat strong{margin-top:8px;font-size:34px;letter-spacing:-.04em}.account-table-card{overflow:hidden}@media(max-width:720px){.account-hero{grid-template-columns:1fr;padding:20px}.account-hero h1{font-size:30px}.account-hero-stat strong{font-size:28px}}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
