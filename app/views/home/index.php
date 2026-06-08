<?php
$announcements = $announcements ?? [];
$latestVersion = $latestVersion ?? null;
$marketStats = $marketStats ?? ['apps' => 0, 'versions' => 0, 'sites' => 0];
$marketItems = $marketItems ?? [];
?>
<section class="apple-hero official-hero">
  <p class="eyebrow">ClayBBS Official Platform</p>
  <h1 class="apple-title">授权、更新、市场<br>统一分发。</h1>
  <p class="apple-subtitle">官方站负责许可证、热更新、插件主题市场与开发者生态，让论坛系统保持稳定、可控、可持续升级。</p>
  <div class="apple-actions">
    <a class="apple-btn primary" href="/index.php?path=me/sites">管理授权</a>
    <a class="apple-btn secondary" href="/index.php?path=history">查看版本树</a>
    <a class="apple-btn secondary" href="/index.php?path=market">浏览应用市场</a>
  </div>
</section>

<section class="home-metrics-grid" aria-label="平台数据">
  <div class="card metric-card"><span>授权站点</span><strong><?= (int)($marketStats['sites'] ?? 0) ?></strong><em>License Sites</em></div>
  <div class="card metric-card"><span>市场应用</span><strong><?= (int)($marketStats['apps'] ?? 0) ?></strong><em>Plugins & Themes</em></div>
  <div class="card metric-card"><span>发布版本</span><strong><?= (int)($marketStats['versions'] ?? 0) ?></strong><em>Release Packages</em></div>
</section>

<section class="apple-section soft">
  <p class="eyebrow">Workflow</p>
  <h2>少一点操作，多一点秩序</h2>
  <p>绑定授权、检查更新、下载插件主题、查看文档，一套官方流程清晰集中。</p>
  <div class="feature-strip">
    <a href="/index.php?path=me/sites"><strong>授权中心</strong><span>域名绑定、授权检查、站点额度管理</span></a>
    <a href="/index.php?path=history"><strong>版本树</strong><span>完整包与热更新包关系一目了然</span></a>
    <a href="/index.php?path=market"><strong>应用市场</strong><span>插件、主题、购买与下载</span></a>
    <a href="/index.php?path=devdocs"><strong>开发文档</strong><span>接口、签名、市场接入说明</span></a>
  </div>
</section>

<section class="apple-section dark home-release-panel">
  <p class="eyebrow">Release Control</p>
  <h2>热更新只做正确的事</h2>
  <p>完整包、增量包、授权校验、下载记录与版本图谱统一管理，论坛后台按官方接口消费。</p>
  <div class="apple-actions">
    <a class="apple-btn primary" href="/index.php?path=history">打开版本树</a>
    <a class="apple-btn secondary" href="/index.php?path=devdocs" style="border-color:rgba(255,255,255,.35)!important;color:#fff!important;background:rgba(255,255,255,.08)!important">查看接入文档</a>
  </div>
</section>

<?php if (!empty($marketItems)): ?>
<section class="apple-section soft market-home-section">
  <p class="eyebrow">Marketplace</p>
  <h2>最新上架</h2>
  <p>精选插件与主题，保持和官方授权体系一致。</p>
  <div class="home-market-grid">
    <?php foreach ($marketItems as $item): ?>
      <a class="home-market-card" href="/index.php?path=market/detail&id=<?= (int)$item['id'] ?>">
        <?php if (!empty($item['logo'])): ?>
          <img class="home-market-icon" src="<?= htmlspecialchars((string)$item['logo']) ?>" alt="">
        <?php else: ?>
          <div class="home-market-icon <?= ($item['type'] ?? '') === 'theme' ? 'theme' : 'plugin' ?>"><?= ($item['type'] ?? '') === 'theme' ? 'T' : 'P' ?></div>
        <?php endif; ?>
        <span class="badge"><?= ($item['type'] ?? '') === 'theme' ? '主题模板' : '功能插件' ?></span>
        <strong><?= htmlspecialchars((string)($item['name'] ?? '')) ?></strong>
        <small>v<?= htmlspecialchars((string)($item['version'] ?? '')) ?></small>
        <em><?= (float)($item['price'] ?? 0) > 0 ? '￥' . htmlspecialchars((string)$item['price']) : '免费' ?></em>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="apple-actions" style="justify-content:center;margin-top:24px"><a class="apple-btn secondary" href="/index.php?path=market">查看全部软件</a></div>
</section>
<?php endif; ?>

<?php if (!empty($announcements)): ?>
<section class="apple-section home-announcement">
  <p class="eyebrow">Announcement</p>
  <h2><?= htmlspecialchars((string)($announcements[0]['title'] ?? '最新公告')) ?></h2>
  <p><?= htmlspecialchars((string)($announcements[0]['content'] ?? '')) ?></p>
  <div class="apple-actions"><a class="apple-btn secondary" href="/index.php?path=history">查看更新记录</a></div>
</section>
<?php endif; ?>

<style>
.official-hero .apple-title{max-width:980px}.home-metrics-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:-48px auto 46px;position:relative;z-index:2}.metric-card span{color:var(--muted);font-weight:700;font-size:13px;text-transform:uppercase;letter-spacing:.08em}.metric-card strong{display:block;font-size:44px;font-weight:300;letter-spacing:-.8px;margin:8px 0;color:var(--ink);font-feature-settings:"tnum"}.metric-card em{font-style:normal;color:var(--muted);font-size:13px}.home-release-panel{border-radius:var(--radius);box-shadow:var(--shadow);margin:38px 0!important}.market-home-section{padding-top:70px}.home-market-grid{width:min(980px,100%);margin:30px auto 0;display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:14px}.home-market-card{display:flex;flex-direction:column;align-items:flex-start;gap:10px;text-align:left;background:#fff;border:1px solid var(--line);border-radius:var(--radius);padding:18px;box-shadow:var(--shadow-soft);transition:.18s}.home-market-card:hover{transform:translateY(-3px);box-shadow:var(--shadow);border-color:var(--primary-border)}.home-market-icon{width:54px;height:54px;border-radius:10px;display:grid;place-items:center;object-fit:cover;background:var(--primary-soft);color:var(--primary);font-weight:800;font-size:22px}.home-market-icon.theme{background:#fff0fb;color:#be185d}.home-market-card strong{font-size:20px;line-height:1.2;color:var(--ink);font-weight:500}.home-market-card small{font-size:13px;color:var(--muted);font-weight:700}.home-market-card em{font-style:normal;color:var(--primary);font-weight:800}.home-announcement{background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow-soft);margin-top:38px}@media(max-width:760px){.home-metrics-grid{grid-template-columns:1fr;margin:0 auto 28px}.home-market-grid{grid-template-columns:1fr}.market-home-section{padding-left:18px!important;padding-right:18px!important}}
</style>
