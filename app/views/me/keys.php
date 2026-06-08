<?php $pageTitle='我的公钥'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card account-hero">
  <div><span class="badge">Public Keys</span><h1>我的公钥</h1><p>这里预留用户公钥页面，不和用户个人主页堆在一起。后续接授权密钥体系时只改这个页面。</p></div>
  <div class="account-hero-stat"><span>当前状态</span><strong>未开放</strong><small>授权仍使用 site_id / token / license_key</small></div>
</section>
<div class="card empty-feature-card"><h2>公钥功能</h2><p class="muted">暂未开放。当前论坛授权主要使用「我的授权」里的 site_id / token / license_key。</p><a class="btn btn-light" href="/index.php?path=me/sites">前往我的授权</a></div>
<style>.account-hero{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:20px;align-items:stretch;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.account-hero h1{margin:14px 0 10px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.account-hero p{color:#64748b;line-height:1.8}.account-hero-stat{display:grid;align-content:center;gap:8px;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.78);box-shadow:0 18px 50px rgba(37,99,235,.10);padding:18px}.account-hero-stat span,.account-hero-stat small{color:#64748b;font-size:13px;font-weight:800}.account-hero-stat strong{font-size:28px;letter-spacing:-.04em}.empty-feature-card{display:grid;gap:12px}.empty-feature-card p{line-height:1.8}@media(max-width:720px){.account-hero{grid-template-columns:1fr;padding:20px}.account-hero h1{font-size:30px}}</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
