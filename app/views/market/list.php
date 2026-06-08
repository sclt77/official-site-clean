<?php
function dev_level_label(string $level): string { return ['professional'=>'专业开发者','official'=>'官方开发者','normal'=>'普通开发者','public'=>'公益开发者'][$level] ?? '开发者'; }
function dev_level_class(string $level): string { return in_array($level, ['professional','official'], true) ? $level : 'normal'; }
function market_query(array $params): string { $base = ['path'=>'market']; foreach ($params as $k=>$v) { if ($v === '' || $v === null || $v === 0) unset($base[$k]); else $base[$k]=$v; } return '/index.php?' . http_build_query($base); }
?>
<?php
$pageTitle = $marketTitle ?? '应用市场';
$items = $items ?? [];
$marketType = $marketType ?? '';
$categoryId = (int)($categoryId ?? ($_GET['category_id'] ?? 0));
$pluginCategories = $pluginCategories ?? [];
$themeCategories = $themeCategories ?? [];
$visibleCategories = $marketType === 'theme' ? $themeCategories : ($marketType === 'plugin' ? $pluginCategories : array_merge($pluginCategories, $themeCategories));
require dirname(__DIR__) . '/layouts/main.php';
?>
<section class="market-hero card">
  <div class="market-hero-copy">
    <span class="badge">ClayBBS Ecosystem</span>
    <h1>官方应用市场</h1>
    <p>为 ClayBBS 挑选可信插件与主题。授权、购买、下载与论坛后台安装链路统一打通，让站点扩展更安全、更清晰。</p>
    <div class="market-hero-actions"><a class="btn" href="/index.php?path=me/purchases">我的购买</a><a class="btn btn-light" href="/index.php?path=developer">发布应用</a></div>
  </div>
  <div class="market-hero-aside"><strong><?= count($items) ?></strong><span>当前筛选应用</span><em>Plugin / Theme / License</em></div>
</section>

<div class="market-tabs card">
  <a class="<?= $marketType===''?'active':'' ?>" href="/index.php?path=market">全部</a>
  <a class="<?= $marketType==='plugin'?'active':'' ?>" href="/index.php?path=market&type=plugin">插件</a>
  <a class="<?= $marketType==='theme'?'active':'' ?>" href="/index.php?path=market&type=theme">主题模板</a>
</div>

<?php if (!empty($visibleCategories)): ?>
<div class="market-tabs market-category-tabs card">
  <a class="<?= $categoryId===0?'active':'' ?>" href="<?= htmlspecialchars(market_query(['type'=>$marketType])) ?>">全部分类</a>
  <?php foreach ($visibleCategories as $cat): ?>
    <a class="<?= $categoryId===(int)$cat['id']?'active':'' ?>" href="<?= htmlspecialchars(market_query(['type'=>$marketType, 'category_id'=>(int)$cat['id']])) ?>"><?= $marketType==='' ? (($cat['type']==='theme'?'主题 / ':'插件 / ')) : '' ?><?= htmlspecialchars($cat['name']) ?></a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="market-grid">
  <?php if ($items): foreach ($items as $item): ?>
    <?php $manifest = json_decode((string)($item['manifest_json'] ?? '{}'), true) ?: []; ?>
    <a class="market-card" href="/index.php?path=market/detail&id=<?= (int)$item['id'] ?>">
      <?php if (!empty($item['logo'])): ?><img class="market-icon" src="<?= htmlspecialchars($item['logo']) ?>" alt=""><?php else: ?><div class="market-icon <?= $item['type']==='theme'?'theme':'plugin' ?>"><?= $item['type']==='theme'?'T':'P' ?></div><?php endif; ?>
      <div class="market-body">
        <div class="market-card-head">
          <h3><?= htmlspecialchars($item['name']) ?></h3>
          <span><?= $item['type']==='theme'?'主题':'插件' ?></span>
        </div>
        <?php if (!empty($item['category_name'])): ?><div class="category-line"><?= htmlspecialchars((string)$item['category_name']) ?></div><?php endif; ?>
        <p><?= htmlspecialchars((string)($item['description'] ?? '')) ?></p>
        <div class="market-meta">
          <span>v<?= htmlspecialchars((string)$item['version']) ?></span>
          <span><?= htmlspecialchars((string)($item['developer_name'] ?: preg_replace('/@.*$/', '', (string)($item['developer_email'] ?? '')) ?: ($item['author'] ?? '开发者'))) ?> <em class="dev-badge <?= dev_level_class((string)($item['developer_level'] ?? 'normal')) ?>"><?= dev_level_label((string)($item['developer_level'] ?? 'normal')) ?></em></span>
          <span><?= (int)($item['downloads'] ?? 0) ?> 下载</span>
        </div>
      </div>
      <div class="market-foot">
        <strong><?= (float)($item['price'] ?? 0) > 0 ? '￥' . htmlspecialchars((string)$item['price']) : '免费' ?></strong>
        <span>请在论坛后台获取/安装</span>
      </div>
    </a>
  <?php endforeach; else: ?>
    <div class="card" style="grid-column:1/-1;text-align:center;color:#94a3b8;padding:46px;">暂无应用</div>
  <?php endif; ?>
</div>

<style>
.market-hero{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:18px;align-items:center;background:radial-gradient(circle at 12% 20%,rgba(37,99,235,.16),transparent 28%),linear-gradient(135deg,#f8fbff,#fff 55%,#ecfeff);border-color:#dbeafe;padding:32px;border-radius:24px}.market-hero h1{font-size:42px;margin:14px 0 8px;letter-spacing:-.055em;line-height:1.1}.market-hero p{color:#64748b;line-height:1.85;max-width:760px}.market-hero-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.market-hero-aside{border:1px solid #e2e8f0;background:rgba(255,255,255,.76);border-radius:20px;padding:18px;box-shadow:0 16px 42px rgba(15,23,42,.08)}.market-hero-aside strong{display:block;font-size:42px;letter-spacing:-.05em}.market-hero-aside span{display:block;color:#64748b;font-weight:900}.market-hero-aside em{display:block;margin-top:14px;font-style:normal;color:#2563eb;font-size:12px;font-weight:950;letter-spacing:.08em}.market-tabs{display:flex;gap:10px;align-items:center;flex-wrap:wrap;padding:12px;border-radius:18px}.market-tabs a{padding:9px 14px;border-radius:999px;background:#f8fafc;color:#64748b;text-decoration:none;font-weight:850;font-size:14px}.market-tabs a.active,.market-tabs a:hover{background:#0f172a;color:#fff}.market-category-tabs a{font-size:13px;padding:8px 12px}.market-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:16px}.market-card{background:#fff;border:1px solid #e2e8f0;border-radius:20px;padding:18px;box-shadow:0 10px 28px rgba(15,23,42,.045);display:flex;flex-direction:column;gap:14px;min-width:0;text-decoration:none;color:inherit;overflow:hidden}.market-card:hover{border-color:#bfdbfe;box-shadow:0 18px 44px rgba(15,23,42,.09);transform:translateY(-2px)}.market-icon{width:52px;height:52px;min-width:52px;border-radius:16px;display:grid;place-items:center;background:#eff6ff;color:#2563eb;font-size:20px;font-weight:900;object-fit:cover}.market-icon.theme{background:#fdf2f8;color:#be185d}.market-card-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}.market-card h3{font-size:17px;margin:0;color:#0f172a;text-decoration:none}.market-card-head span{flex:0 0 auto;font-size:12px;font-weight:900;color:#2563eb;background:#eff6ff;border-radius:999px;padding:5px 8px;text-decoration:none}.category-line{display:inline-flex;margin-top:8px;border-radius:999px;padding:4px 8px;background:#f8fafc;color:#64748b;font-size:12px;font-weight:800}.market-body p{margin:8px 0 0;color:#64748b;font-size:13px;line-height:1.7;min-height:42px;text-decoration:none}.market-meta{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.market-meta span{font-size:12px;color:#94a3b8;background:#f8fafc;border-radius:999px;padding:5px 8px;line-height:1.5;text-decoration:none}.market-foot{border-top:1px solid #f1f5f9;padding-top:12px;display:flex;justify-content:space-between;gap:10px;align-items:center}.market-foot strong{color:#0f172a;text-decoration:none}.market-foot span{font-size:12px;color:#94a3b8;text-decoration:none}@media(max-width:760px){.market-hero{grid-template-columns:1fr;padding:22px}.market-hero h1{font-size:32px}.market-hero-aside{display:none}}@media(max-width:640px){.market-hero{padding:20px}.market-hero h1{font-size:28px}.market-grid{grid-template-columns:1fr}.market-card{padding:14px;border-radius:16px}.market-card-head{align-items:center}.market-meta span{max-width:100%;overflow-wrap:anywhere}.market-foot{align-items:flex-start}.market-foot span{text-align:right}}
.dev-badge{font-style:normal;margin-left:4px;border-radius:999px;padding:2px 6px;font-size:11px;background:#f1f5f9;color:#64748b;white-space:nowrap}.dev-badge.professional{background:#ecfdf5;color:#047857}.dev-badge.official{background:#eff6ff;color:#1d4ed8}</style>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
