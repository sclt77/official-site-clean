<?php
function dev_level_label(string $level): string { return ['professional'=>'专业开发者','official'=>'官方开发者','normal'=>'普通开发者','public'=>'公益开发者'][$level] ?? '开发者'; }
function dev_level_class(string $level): string { return in_array($level, ['professional','official'], true) ? $level : 'normal'; }
?>
<?php $pageTitle = $item['name'] ?? '应用详情'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card market-detail-hero">
  <div class="detail-title-row">
    <?php if (!empty($item['logo'])): ?><img class="detail-logo" src="<?= htmlspecialchars($item['logo']) ?>" alt=""><?php else: ?><div class="detail-logo detail-logo-fallback"><?= $item['type']==='theme'?'T':'P' ?></div><?php endif; ?>
    <div class="detail-title-main">
      <div class="detail-tags"><span class="badge"><?= $item['type']==='theme'?'主题模板':'插件' ?></span><?php if (!empty($item['category_name'])): ?><span class="category-pill"><?= htmlspecialchars((string)$item['category_name']) ?></span><?php endif; ?></div>
      <h1><?= htmlspecialchars($item['name']) ?></h1>
      <p><?= nl2br(htmlspecialchars((string)($item['description'] ?? ''))) ?></p>
    </div>
  </div>
  <aside class="market-price-card">
    <span>当前版本 v<?= htmlspecialchars($item['version']) ?></span>
    <strong><?= (float)$item['price']>0?'￥'.htmlspecialchars((string)$item['price']):'免费' ?></strong>
    <small><?= (int)$item['downloads'] ?> 次下载 · <?= (float)$item['price']>0?'付费授权':'免费获取' ?></small>
    <?php if (!empty($license)): ?>
      <a class="btn" href="/index.php?path=me/purchases">查看我的购买</a>
    <?php else: ?>
      <form method="post" action="/index.php?path=market/acquire" data-no-ajax>
        <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn" type="submit"><?= (float)$item['price']>0?'立即购买':'免费获取' ?></button>
      </form>
    <?php endif; ?>
  </aside>
</section>
<?php if (!empty($galleryImages)): ?>
<div class="card">
  <h3>应用展示</h3>
  <div class="detail-gallery">
    <?php foreach ($galleryImages as $idx => $img): ?><button class="detail-gallery-item" type="button" data-gallery-index="<?= (int)$idx ?>" data-gallery-src="<?= htmlspecialchars((string)$img['image_path']) ?>"><img src="<?= htmlspecialchars((string)$img['image_path']) ?>" alt="<?= htmlspecialchars($item['name']) ?> 展示图"></button><?php endforeach; ?>
  </div>
</div>
<div class="gallery-viewer" id="galleryViewer" aria-hidden="true"><div class="gallery-viewer-mask" data-gallery-close></div><button class="gallery-viewer-close" type="button" data-gallery-close aria-label="关闭">×</button><button class="gallery-viewer-nav prev" type="button" data-gallery-prev aria-label="上一张">‹</button><img id="galleryViewerImg" src="" alt="应用展示图"><button class="gallery-viewer-nav next" type="button" data-gallery-next aria-label="下一张">›</button><div class="gallery-viewer-count" id="galleryViewerCount"></div></div>
<?php endif; ?>

<div class="card">
  <h3>应用信息</h3>
  <div class="info-grid"><div><b>Slug</b><span><?= htmlspecialchars($item['slug']) ?></span></div><div><b>分类</b><span><?= htmlspecialchars((string)($item['category_name'] ?? '未分类')) ?></span></div><div><b>作者</b><span><a class="dev-link" href="/index.php?path=user&id=<?= (int)($item['developer_user_id'] ?? 0) ?>"><?= htmlspecialchars((string)($item['developer_name'] ?: preg_replace('/@.*$/', '', (string)($item['developer_email'] ?? '')) ?: ($item['author'] ?? '开发者'))) ?></a> <em class="dev-badge <?= dev_level_class((string)($item['developer_level'] ?? 'normal')) ?>"><?= dev_level_label((string)($item['developer_level'] ?? 'normal')) ?></em></span></div><div><b>下载</b><span><?= (int)$item['downloads'] ?></span></div><div><b>授权规则</b><span><?= (float)$item['price']>0?'付费应用需绑定域名':'免费应用无需绑定域名' ?></span></div></div>
</div>
<div class="card acquire-card">
  <div>
    <h3>授权与安装</h3>
    <p class="muted">获取授权后，可在“我的购买”中绑定域名，并复制授权 Key 到 ClayBBS 论坛后台安装。</p>
  </div>
  <?php if (!empty($license)): ?>
    <div class="license-box"><span>你的授权 Key</span><code><?= htmlspecialchars($license['license_key']) ?></code></div>
    <a class="btn" href="/index.php?path=me/purchases">查看我的购买</a>
  <?php else: ?>
    <form method="post" action="/index.php?path=market/acquire" data-no-ajax><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="btn" type="submit"><?= (float)$item['price']>0?'立即购买':'免费获取' ?></button></form>
    <?php if ((float)$item['price']>0): ?><div class="muted">支付成功后才会生成授权 Key。</div><?php endif; ?>
  <?php endif; ?>
</div>
<style>
.market-detail-hero{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:24px;align-items:stretch;padding:30px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe;overflow:hidden}.detail-title-row{display:flex;gap:16px;align-items:flex-start;min-width:0}.detail-title-main{min-width:0}.detail-tags{display:flex;gap:8px;align-items:center;flex-wrap:wrap}.category-pill{display:inline-flex;border-radius:999px;padding:6px 9px;background:#f8fafc;color:#64748b;font-size:12px;font-weight:900}.detail-logo{width:76px;height:76px;min-width:76px;border-radius:22px;object-fit:cover;border:1px solid #dbeafe;box-shadow:0 16px 42px rgba(37,99,235,.12)}.detail-logo-fallback{display:grid;place-items:center;background:linear-gradient(135deg,#2563eb,#06b6d4);color:#fff;font-size:28px;font-weight:900}.market-detail-hero h1{font-size:40px;margin:14px 0 10px;line-height:1.12;letter-spacing:-.045em;word-break:break-word}.market-detail-hero p{color:#64748b;line-height:1.8;overflow-wrap:anywhere}.market-price-card{display:grid;align-content:center;gap:10px;border:1px solid #dbeafe;border-radius:22px;background:rgba(255,255,255,.78);box-shadow:0 20px 56px rgba(37,99,235,.12);padding:20px;min-width:0}.market-price-card span,.market-price-card small{color:#64748b;font-size:13px;font-weight:800}.market-price-card strong{display:block;font-size:34px;color:#dc2626;letter-spacing:-.04em}.market-price-card form{margin:0}.detail-gallery{display:flex;gap:12px;overflow-x:auto;overflow-y:hidden;padding:2px 2px 10px;scroll-snap-type:x proximity;-webkit-overflow-scrolling:touch}.detail-gallery-item{display:block;flex:0 0 min(520px,82vw);scroll-snap-align:start;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;background:#f8fafc;padding:0;cursor:zoom-in}.detail-gallery img{display:block;width:100%;height:260px;object-fit:cover}.gallery-viewer{display:none;position:fixed;inset:0;z-index:3000;align-items:center;justify-content:center}.gallery-viewer.is-open{display:flex}.gallery-viewer-mask{position:absolute;inset:0;background:rgba(2,6,23,.86);backdrop-filter:blur(4px)}.gallery-viewer img{position:relative;z-index:2;max-width:min(1120px,92vw);max-height:84vh;border-radius:18px;object-fit:contain;box-shadow:0 28px 90px rgba(0,0,0,.45)}.gallery-viewer-close,.gallery-viewer-nav{position:absolute;z-index:3;border:0;background:rgba(255,255,255,.14);color:#fff;cursor:pointer;backdrop-filter:blur(10px)}.gallery-viewer-close{right:22px;top:18px;width:42px;height:42px;border-radius:999px;font-size:30px;line-height:1}.gallery-viewer-nav{top:50%;transform:translateY(-50%);width:48px;height:64px;border-radius:16px;font-size:46px;line-height:1}.gallery-viewer-nav.prev{left:22px}.gallery-viewer-nav.next{right:22px}.gallery-viewer-count{position:absolute;z-index:3;bottom:22px;left:50%;transform:translateX(-50%);border-radius:999px;background:rgba(15,23,42,.64);color:#fff;padding:7px 12px;font-size:13px;font-weight:900}.info-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px}.info-grid div{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:12px;min-width:0}.info-grid b{display:block;color:#64748b;font-size:12px}.info-grid span{display:block;margin-top:6px;font-weight:800;overflow-wrap:anywhere}.acquire-card{display:grid;gap:12px}.license-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px}.license-box span{display:block;color:#64748b;font-size:12px}.license-box code{display:block;margin-top:6px;font-size:18px;word-break:break-all}.dev-link{text-decoration:none!important;font-weight:800;color:#2563eb}.dev-link:hover{text-decoration:underline!important}.dev-badge{font-style:normal;margin-left:4px;border-radius:999px;padding:2px 6px;font-size:11px;background:#f1f5f9;color:#64748b;white-space:nowrap}.dev-badge.professional{background:#ecfdf5;color:#047857}.dev-badge.official{background:#eff6ff;color:#1d4ed8}@media(max-width:820px){.market-detail-hero{grid-template-columns:1fr;padding:20px}.market-detail-hero h1{font-size:30px}.market-price-card strong{font-size:28px}}@media(max-width:640px){.detail-gallery{gap:10px;padding-bottom:8px}.detail-gallery-item{flex-basis:84vw}.detail-gallery img{height:190px}.gallery-viewer img{max-width:94vw;max-height:78vh;border-radius:14px}.gallery-viewer-nav{width:38px;height:54px;font-size:38px}.gallery-viewer-nav.prev{left:10px}.gallery-viewer-nav.next{right:10px}.gallery-viewer-close{right:12px;top:12px}.detail-title-row{gap:12px}.detail-logo{width:58px;height:58px;min-width:58px;border-radius:16px}.market-detail-hero p{font-size:14px;line-height:1.7}.info-grid{grid-template-columns:1fr}.license-box code{font-size:15px}}
</style>

<script>
(function(){
  var items=[].slice.call(document.querySelectorAll('[data-gallery-src]'));
  var viewer=document.getElementById('galleryViewer'),img=document.getElementById('galleryViewerImg'),count=document.getElementById('galleryViewerCount');
  if(!items.length||!viewer||!img)return;
  var current=0;
  function show(i){current=(i+items.length)%items.length;img.src=items[current].dataset.gallerySrc||'';if(count)count.textContent=(current+1)+' / '+items.length;viewer.classList.add('is-open');viewer.setAttribute('aria-hidden','false');document.body.style.overflow='hidden';}
  function close(){viewer.classList.remove('is-open');viewer.setAttribute('aria-hidden','true');img.src='';document.body.style.overflow='';}
  items.forEach(function(el,i){el.addEventListener('click',function(){show(i);});});
  viewer.querySelectorAll('[data-gallery-close]').forEach(function(el){el.addEventListener('click',close);});
  var prev=viewer.querySelector('[data-gallery-prev]'),next=viewer.querySelector('[data-gallery-next]');
  if(prev)prev.addEventListener('click',function(e){e.stopPropagation();show(current-1);});
  if(next)next.addEventListener('click',function(e){e.stopPropagation();show(current+1);});
  document.addEventListener('keydown',function(e){if(!viewer.classList.contains('is-open'))return;if(e.key==='Escape')close();if(e.key==='ArrowLeft')show(current-1);if(e.key==='ArrowRight')show(current+1);});
})();
</script>

</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
