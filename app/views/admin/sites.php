<?php
$pageTitle='站点授权管理';
$product = in_array(($product ?? ($_GET['product'] ?? 'claybbs')), ['claybbs','cutot'], true) ? ($product ?? ($_GET['product'] ?? 'claybbs')) : 'claybbs';
$productLabel = $product === 'cutot' ? 'CUTOT' : 'ClayBBS';
$tabRaw = (string)($_GET['tab'] ?? '');
$pendingLimitRequestCount = (int)($pendingLimitRequestCount ?? count(array_filter($limitRequests ?? [], static fn($r) => ($r['status'] ?? '') === 'pending')));
$hasPendingLimitRequests = $pendingLimitRequestCount > 0;
$tab = in_array($tabRaw, ['sites','requests'], true) ? $tabRaw : ($hasPendingLimitRequests ? 'requests' : 'sites');
$requestStatus = (string)($requestStatus ?? ($_GET['request_status'] ?? ''));
$requestStatusLabels = [''=>'全部','pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝'];
require dirname(__DIR__) . '/layouts/main.php';
?>
<div class="admin-page sites-page">
<div class="card">
  <h2>站点授权管理</h2>
  <div style="color:var(--muted);margin-top:6px;">当前管理：<?= htmlspecialchars($productLabel) ?>。ClayBBS 与 CUTOT 授权分开绑定、审核和禁用。</div>
</div>


<div class="card site-tabs-card">
  <div class="site-tabs" role="tablist" aria-label="产品授权切换">
    <a class="site-tab <?= $product === 'claybbs' ? 'active' : '' ?>" href="/admin.php?path=sites&product=claybbs&tab=sites">ClayBBS 授权</a>
    <a class="site-tab <?= $product === 'cutot' ? 'active' : '' ?>" href="/admin.php?path=sites&product=cutot&tab=sites">CUTOT 授权</a>
  </div>
</div>
<div class="card site-tabs-card">
  <div class="site-tabs" role="tablist">
    <a class="site-tab <?= $tab === 'sites' ? 'active' : '' ?>" href="/admin.php?path=sites&product=<?= urlencode($product) ?>&tab=sites">绑定站点</a>
    <a class="site-tab <?= $tab === 'requests' ? 'active' : '' ?>" href="/admin.php?path=sites&product=<?= urlencode($product) ?>&tab=requests">授权申请审核 <?php if($pendingLimitRequestCount > 0): ?><span><?= $pendingLimitRequestCount ?></span><?php endif; ?></a>
  </div>
</div>

<?php if ($tab === 'requests'): ?>
<div class="card">
  <h3>授权申请审核</h3>
  <div class="muted" style="margin-bottom:10px;">这里显示用户在前台“我的授权 → 申请授权”提交的授权名额申请。当前待审核：<?= (int)$pendingLimitRequestCount ?> 条。</div>
  <div class="site-tabs" style="margin-bottom:12px;">
    <?php foreach ($requestStatusLabels as $statusKey => $statusLabel): ?>
      <a class="site-tab <?= $requestStatus === (string)$statusKey ? 'active' : '' ?>" href="/admin.php?path=sites&product=<?= urlencode($product) ?>&tab=requests<?= $statusKey !== '' ? '&request_status=' . urlencode((string)$statusKey) : '' ?>"><?= htmlspecialchars($statusLabel) ?></a>
    <?php endforeach; ?>
  </div>
  <?php if (!empty($limitRequests)): ?>
  <div class="table-wrap"><table class="table"><thead><tr><th>ID</th><th>产品</th><th>用户</th><th>当前/已用</th><th>申请增加</th><th>原因</th><th>状态</th><th>时间</th><th>操作</th></tr></thead><tbody>
  <?php foreach($limitRequests as $r): ?><tr><td><?= (int)$r['id'] ?></td><td><?= htmlspecialchars(strtoupper((string)($r['product'] ?? $product))) ?></td><td><?= htmlspecialchars((string)($r['name'] ?: $r['email'])) ?><br><span class="muted">UID <?= (int)$r['user_id'] ?> · <?= htmlspecialchars((string)($r['email'] ?? '')) ?></span></td><td><?= (int)$r['current_limit'] ?> / <?= (int)$r['current_used'] ?><br><span class="muted">当前 <?= htmlspecialchars($productLabel) ?> 上限 <?= (int)($r[$product === 'cutot' ? 'cutot_site_limit' : 'claybbs_site_limit'] ?? 0) ?></span></td><td>+<?= (int)$r['requested_count'] ?></td><td><?= nl2br(htmlspecialchars((string)($r['reason'] ?? ''))) ?></td><td><span class="status-pill status-<?= htmlspecialchars((string)$r['status']) ?>"><?= htmlspecialchars(['pending'=>'待审核','approved'=>'已通过','rejected'=>'已拒绝'][(string)$r['status']] ?? (string)$r['status']) ?></span></td><td><?= htmlspecialchars((string)$r['created_at']) ?></td><td><?php if(($r['status'] ?? '')==='pending'): ?><form method="post" action="/admin.php?path=sites/limit-request" style="display:grid;gap:6px;min-width:180px;"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>"><input class="input" name="review_note" placeholder="审核备注；拒绝时会显示给申请者"><div style="display:flex;gap:6px;"><button class="btn" name="action" value="approve">通过</button><button class="btn btn-light" name="action" value="reject" style="color:#dc2626;">拒绝</button></div></form><?php else: ?><span class="muted"><?= htmlspecialchars((string)($r['review_note'] ?? '')) ?></span><?php endif; ?></td></tr><?php endforeach; ?>
  </tbody></table></div>
  <?php else: ?><div class="muted">暂无授权申请<?php if ($requestStatus !== ''): ?>（当前筛选：<?= htmlspecialchars($requestStatusLabels[$requestStatus] ?? $requestStatus) ?>）<?php endif; ?></div><?php endif; ?>
</div>
<?php else: ?>
<div class="card">
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>用户ID</th><th>账号</th><th>邮箱</th><th>绑定站点数</th><th>操作</th></tr></thead>
    <tbody>
    <?php foreach ($users as $u): ?>
      <?php $uid = (int) $u['user_id']; $list = $sitesByUser[$uid] ?? []; ?>
      <tr>
        <td><?= $uid ?></td>
        <td><?= htmlspecialchars((string)($u['user_name'] ?? '')) ?></td>
        <td><?= htmlspecialchars((string)($u['user_email'] ?? '')) ?></td>
        <td><?= count($list) ?></td>
        <td>
          <button class="btn btn-light" type="button" onclick="openSitesModal(<?= $uid ?>)">查看绑定站点</button>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>

<?php foreach ($users as $u): ?>
<?php $uid = (int) $u['user_id']; $list = $sitesByUser[$uid] ?? []; ?>
<div class="modal" id="sites-modal-<?= $uid ?>" style="display:none;">
  <div class="modal-mask" onclick="closeSitesModal(<?= $uid ?>)"></div>
  <div class="modal-box">
    <div class="modal-head">
      <div>用户：<?= htmlspecialchars((string)($u['user_email'] ?? '')) ?></div>
      <button class="btn btn-light" type="button" onclick="closeSitesModal(<?= $uid ?>)">关闭</button>
    </div>
    <div class="table-wrap sites-modal-wrap">
      <table class="table sites-modal-table">
        <thead><tr><th>产品</th><th>域名</th><th>邮箱</th><th>Site ID</th><th>Token</th><th>授权码</th><th>授权类型</th><th>状态</th><th>最后活跃</th><th>操作</th></tr></thead>
        <tbody>
        <?php foreach ($list as $site): ?>
          <tr>
            <td><?= htmlspecialchars(strtoupper((string)($site['product'] ?? $product))) ?></td><td><?= htmlspecialchars($site['domain']) ?></td>
            <td><?= htmlspecialchars($site['email']) ?></td>
            <td><span class="site-code"><?= htmlspecialchars($site['site_id']) ?></span></td>
            <td><span class="site-code site-code-token"><?= htmlspecialchars($site['token']) ?></span></td>
            <td><span class="site-code site-code-license"><?= htmlspecialchars((string)($site['license_key'] ?? '')) ?></span></td>
            <td>
              <?php $lt=(string)($site['license_type'] ?? 'permanent'); $isTrial = ($lt === 'trial'); echo $isTrial ? '体验授权' : '永久授权'; ?>
              <?php if($isTrial): ?><br><span class="muted">到期：<?= htmlspecialchars((string)($site['license_expires_at'] ?? '')) ?></span><?php endif; ?>
              <form method="post" action="/admin.php?path=sites/update" class="license-convert-mini">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="convert_license">
                <input type="hidden" name="id" value="<?= (int)$site['id'] ?>">
                <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
                <input type="hidden" name="license_type" value="<?= $isTrial ? 'permanent' : 'trial' ?>">
                <?php if (!$isTrial): ?><input class="input" type="datetime-local" name="license_expires_at" value="<?= date('Y-m-d\TH:i', time()+7*86400) ?>"><?php endif; ?>
                <button class="btn btn-light" type="submit"><?= $isTrial ? '转永久授权' : '转体验授权' ?></button>
              </form>
            </td>
            <td><?= htmlspecialchars((string)($site['license_status'] ?? $site['status'])) ?></td>
            <td><?= htmlspecialchars((string)($site['last_seen_at'] ?? '')) ?></td>
            <td>
              <form method="post" action="/admin.php?path=sites/update" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:6px;">
                <?= csrf_field() ?>
                <input type="hidden" name="_action" value="toggle_status">
                <input type="hidden" name="id" value="<?= (int)$site['id'] ?>">
                <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
                <select class="select" name="status" style="width:100px;">
                  <option value="active" <?= $site['status']==='active'?'selected':'' ?>>启用</option>
                  <option value="disabled" <?= $site['status']==='disabled'?'selected':'' ?>>禁用</option>
                  <option value="locked" <?= ($site['status'] ?? '')==='locked'||($site['license_status'] ?? '')==='locked'?'selected':'' ?>>锁定</option>
                </select>
                <button class="btn btn-light" type="submit">保存</button>
              </form>
              <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <form method="post" action="/admin.php?path=sites/update" onsubmit="return confirm('确认重置该站点 token 吗？');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="reset_token">
                  <input type="hidden" name="id" value="<?= (int)$site['id'] ?>">
                <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
                  <button class="btn btn-light" type="submit">重置 Token</button>
                </form>
                <a class="btn btn-light" href="/admin.php?path=logs&site_id=<?= urlencode((string)$site['site_id']) ?>">查看日志</a>
                <form method="post" action="/admin.php?path=sites/update" onsubmit="return confirm('确认删除该授权绑定？删除后该站点凭据会立即失效。');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="_action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$site['id'] ?>">
                <input type="hidden" name="product" value="<?= htmlspecialchars($product) ?>">
                  <button class="btn btn-light" type="submit" style="color:#dc2626;">删除绑定</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<style>
.site-tabs-card{padding:10px}.site-tabs{display:flex;gap:8px;flex-wrap:wrap}.site-tab{display:inline-flex;align-items:center;gap:7px;height:38px;padding:0 14px;border-radius:999px;background:#f8fafc;border:1px solid #e2e8f0;color:#64748b;text-decoration:none;font-weight:900;font-size:13px}.site-tab.active,.site-tab:hover{background:#0284c7;border-color:#0284c7;color:#fff}.site-tab span{display:inline-flex;min-width:20px;height:20px;padding:0 6px;border-radius:999px;align-items:center;justify-content:center;background:#ef4444;color:#fff;font-size:11px}.modal{position:fixed;inset:0;z-index:50;display:flex;align-items:center;justify-content:center;}.modal-mask{position:absolute;inset:0;background:rgba(0,0,0,.35);}.modal-box{position:relative;background:#fff;border-radius:12px;max-width:1040px;width:92%;padding:16px;box-shadow:0 10px 30px rgba(15,23,42,.2);}.modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;font-weight:600;gap:12px}.modal-head>div{min-width:0;overflow-wrap:anywhere}.sites-modal-wrap{max-height:62vh;overflow:auto!important;padding:0!important}.sites-modal-wrap:before{content:'左右滑动查看更多';display:block;padding:10px 12px;color:var(--muted);font-size:12px;background:var(--surface-soft);border-bottom:1px solid var(--line);position:sticky;left:0}.sites-modal-table{min-width:1500px!important;table-layout:fixed!important}.sites-modal-table th,.sites-modal-table td{vertical-align:top;white-space:normal!important;overflow:hidden}.sites-modal-table th:nth-child(1),.sites-modal-table td:nth-child(1){width:150px}.sites-modal-table th:nth-child(2),.sites-modal-table td:nth-child(2){width:190px}.sites-modal-table th:nth-child(3),.sites-modal-table td:nth-child(3){width:150px}.sites-modal-table th:nth-child(4),.sites-modal-table td:nth-child(4){width:260px}.sites-modal-table th:nth-child(5),.sites-modal-table td:nth-child(5){width:230px}.sites-modal-table th:nth-child(6),.sites-modal-table td:nth-child(6){width:90px}.sites-modal-table th:nth-child(7),.sites-modal-table td:nth-child(7){width:160px}.sites-modal-table th:nth-child(8),.sites-modal-table td:nth-child(8){width:250px}.site-code{display:block;max-width:100%;font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:12px;line-height:1.55;color:#26364d;background:#f8fafc;border:1px solid #e2e8f0;border-radius:6px;padding:7px 8px;white-space:normal;overflow-wrap:anywhere;word-break:break-all}.site-code-token,.site-code-license{min-height:36px}.license-convert-mini{margin-top:8px;display:grid;gap:6px;min-width:150px}.license-convert-mini .input{width:150px;height:34px;padding:0 8px}.license-convert-mini .btn{height:34px;padding:0 10px;white-space:nowrap}.sites-modal-table th:nth-child(9),.sites-modal-table td:nth-child(9){width:160px}.sites-modal-table th:nth-child(10),.sites-modal-table td:nth-child(10){width:260px}@media(max-width:760px){.site-tabs{flex-wrap:nowrap;overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:2px}.site-tab{flex:0 0 auto;white-space:nowrap;justify-content:center}.modal{align-items:flex-start;padding:74px 12px 18px}.modal-box{width:100%;max-width:100%;max-height:calc(100vh - 96px);overflow:auto;border-radius:12px;padding:14px}.modal-head{align-items:flex-start}.modal-head .btn{flex:0 0 42%;min-width:110px}.sites-modal-wrap{max-height:58vh;border-radius:8px!important}.sites-modal-table{min-width:1500px!important}.site-code{font-size:11.5px;line-height:1.45;padding:6px}}
</style>
<script>
function openSitesModal(uid){var el=document.getElementById('sites-modal-'+uid);if(el)el.style.display='flex';}
function closeSitesModal(uid){var el=document.getElementById('sites-modal-'+uid);if(el)el.style.display='none';}
</script>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
