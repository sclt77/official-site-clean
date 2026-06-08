<?php
$pageTitle='站点设置';
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = (string)($_SERVER['HTTP_HOST'] ?? 'www.claybbs.com');
$alipayNotifyUrl = $scheme . '://' . $host . '/api.php?path=market/pay-notify';
require dirname(__DIR__) . '/layouts/main.php';
?>
<div class="card">
  <h2>站点设置</h2>
  <div class="muted">修改官方更新平台基础信息、邮箱 SMTP、支付宝当面付等配置。</div>
</div>

<?php if (!empty($error)): ?><div class="card" style="background:#fee2e2;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if (!empty($success)): ?><div class="card" style="background:#dcfce7;color:#166534;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<div class="card settings-tabs-card">
  <div class="settings-tabs" role="tablist">
    <button type="button" class="settings-tab active" data-tab="base">基础设置</button>
    <button type="button" class="settings-tab" data-tab="mail">邮箱 SMTP</button>
    <button type="button" class="settings-tab" data-tab="pay">支付宝支付</button>
    <button type="button" class="settings-tab" data-tab="developer">开发者分成</button>
  </div>

  <form method="post" class="settings-form">
    <?= csrf_field() ?>

    <section class="settings-panel active" data-panel="base">
      <div class="grid settings-grid">
        <div>
          <label>站点名称</label>
          <input class="input" name="site_name" value="<?= htmlspecialchars((string)($settings['site_name'] ?? 'Clay官方站')) ?>" required>
        </div>
        <div>
          <label>Logo 文字</label>
          <input class="input" name="site_logo_text" value="<?= htmlspecialchars((string)($settings['site_logo_text'] ?? 'Clay官方站')) ?>" required>
        </div>
        <div class="full-row">
          <label>首页副标题</label>
          <input class="input" name="site_tagline" value="<?= htmlspecialchars((string)($settings['site_tagline'] ?? '')) ?>">
        </div>
        <div class="full-row">
          <label>页脚文案</label>
          <input class="input" name="footer_text" value="<?= htmlspecialchars((string)($settings['footer_text'] ?? '')) ?>">
        </div>
        <div class="setting-note full-row">
          <label class="check-label"><input type="checkbox" name="user_site_unbind_enabled" value="1" <?= !empty($settings['user_site_unbind_enabled']) && (string)$settings['user_site_unbind_enabled']==='1' ? 'checked' : '' ?>> 允许用户自助解除授权绑定</label>
          <div class="muted">开启后，用户可在“我的授权”中删除自己的站点绑定；删除后该站点的 site_id、token、license_key 会立即失效，绑定名额释放。</div>
        </div>
        <div class="setting-note full-row product-limit-settings">
          <strong>ClayBBS 授权绑定条件</strong>
          <label class="check-label"><input type="checkbox" name="claybbs_auth_purchase_enabled" value="1" <?= !empty($settings['claybbs_auth_purchase_enabled']) && (string)$settings['claybbs_auth_purchase_enabled']==='1' ? 'checked' : '' ?>> 开启 ClayBBS 购买授权名额</label>
          <label class="check-label"><input type="checkbox" name="claybbs_site_limit_request_enabled" value="1" <?= !empty($settings['claybbs_site_limit_request_enabled']) && (string)$settings['claybbs_site_limit_request_enabled']==='1' ? 'checked' : '' ?>> 开启 ClayBBS 申请授权名额</label>
          <div class="product-limit-grid">
            <div><label>ClayBBS 授权单价</label><input class="input" type="number" step="0.01" min="0" name="claybbs_auth_purchase_price" value="<?= htmlspecialchars((string)($settings['claybbs_auth_purchase_price'] ?? '0.00')) ?>"></div>
            <div><label>ClayBBS 单次最多购买</label><input class="input" type="number" min="1" name="claybbs_auth_purchase_max" value="<?= htmlspecialchars((string)($settings['claybbs_auth_purchase_max'] ?? '10')) ?>"></div>
            <div><label>ClayBBS 单次最多申请</label><input class="input" type="number" min="1" name="claybbs_site_limit_request_max" value="<?= htmlspecialchars((string)($settings['claybbs_site_limit_request_max'] ?? '1')) ?>"></div>
          </div>
        </div>
        <div class="setting-note full-row product-limit-settings">
          <strong>CUTOT 授权绑定条件</strong>
          <label class="check-label"><input type="checkbox" name="cutot_auth_purchase_enabled" value="1" <?= !empty($settings['cutot_auth_purchase_enabled']) && (string)$settings['cutot_auth_purchase_enabled']==='1' ? 'checked' : '' ?>> 开启 CUTOT 购买授权名额</label>
          <label class="check-label"><input type="checkbox" name="cutot_site_limit_request_enabled" value="1" <?= !empty($settings['cutot_site_limit_request_enabled']) && (string)$settings['cutot_site_limit_request_enabled']==='1' ? 'checked' : '' ?>> 开启 CUTOT 申请授权名额</label>
          <div class="product-limit-grid">
            <div><label>CUTOT 授权单价</label><input class="input" type="number" step="0.01" min="0" name="cutot_auth_purchase_price" value="<?= htmlspecialchars((string)($settings['cutot_auth_purchase_price'] ?? '0.00')) ?>"></div>
            <div><label>CUTOT 单次最多购买</label><input class="input" type="number" min="1" name="cutot_auth_purchase_max" value="<?= htmlspecialchars((string)($settings['cutot_auth_purchase_max'] ?? '10')) ?>"></div>
            <div><label>CUTOT 单次最多申请</label><input class="input" type="number" min="1" name="cutot_site_limit_request_max" value="<?= htmlspecialchars((string)($settings['cutot_site_limit_request_max'] ?? '1')) ?>"></div>
          </div>
        </div>
      </div>
    </section>

    <section class="settings-panel" data-panel="mail">
      <div class="grid settings-grid">
        <div class="setting-note full-row">
          <label class="check-label"><input type="checkbox" name="email_verify_enabled" value="1" <?= !empty($settings['email_verify_enabled']) && (string)$settings['email_verify_enabled']==='1' ? 'checked' : '' ?>> 开启注册邮箱验证</label>
          <div class="muted">开启后，新注册用户需要点击验证邮件后才能登录。</div>
        </div>
        <div><label>SMTP 主机</label><input class="input" name="smtp_host" value="<?= htmlspecialchars((string)($settings['smtp_host'] ?? '')) ?>" placeholder="smtp.example.com"></div>
        <div><label>SMTP 端口</label><input class="input" type="number" name="smtp_port" value="<?= htmlspecialchars((string)($settings['smtp_port'] ?? '587')) ?>"></div>
        <div><label>加密方式</label><select class="select" name="smtp_secure"><option value="tls" <?= (($settings['smtp_secure'] ?? 'tls')==='tls')?'selected':'' ?>>TLS / STARTTLS</option><option value="ssl" <?= (($settings['smtp_secure'] ?? '')==='ssl')?'selected':'' ?>>SSL</option><option value="none" <?= (($settings['smtp_secure'] ?? '')==='none')?'selected':'' ?>>不加密</option></select></div>
        <div><label>SMTP 用户名</label><input class="input" name="smtp_username" value="<?= htmlspecialchars((string)($settings['smtp_username'] ?? '')) ?>"></div>
        <div>
          <label>SMTP 密码/授权码</label>
          <input class="input" type="password" name="smtp_password" value="" placeholder="<?= !empty($settings['smtp_password']) ? '已保存，留空则不修改' : '请输入密码或授权码' ?>">
          <?php if (!empty($settings['smtp_password'])): ?>
            <label class="sub-check"><input type="checkbox" name="smtp_password_clear" value="1"> 清空已保存密码</label>
          <?php endif; ?>
        </div>
        <div><label>发件邮箱</label><input class="input" name="smtp_from_email" value="<?= htmlspecialchars((string)($settings['smtp_from_email'] ?? '')) ?>"></div>
        <div><label>发件人名称</label><input class="input" name="smtp_from_name" value="<?= htmlspecialchars((string)($settings['smtp_from_name'] ?? ($settings['site_name'] ?? 'Clay官方站'))) ?>"></div>
      </div>
    </section>

    <section class="settings-panel" data-panel="pay">
      <div class="grid settings-grid">
        <div class="setting-note full-row">
          <label class="check-label"><input type="checkbox" name="alipay_enabled" value="1" <?= !empty($settings['alipay_enabled']) && (string)$settings['alipay_enabled']==='1' ? 'checked' : '' ?>> 开启支付宝官方当面付</label>
          <div class="muted">使用支付宝开放平台 <code>alipay.trade.precreate</code> 生成扫码支付二维码，支付成功后自动发放应用授权 Key。</div>
        </div>
        <div class="callback-box full-row">
          <label>支付宝异步通知回调地址</label>
          <div class="callback-line"><code id="alipayNotifyUrl"><?= htmlspecialchars($alipayNotifyUrl) ?></code><button class="btn btn-light tiny-btn" type="button" onclick="copyAlipayNotifyUrl()">复制</button></div>
          <div class="muted">把这个地址填到支付宝开放平台对应应用/产品的异步通知地址里；系统发起预下单时也会自动带上该地址。</div>
        </div>
        <div><label>支付宝网关</label><input class="input" name="alipay_gateway" value="<?= htmlspecialchars((string)($settings['alipay_gateway'] ?? 'https://openapi.alipay.com/gateway.do')) ?>" placeholder="https://openapi.alipay.com/gateway.do"></div>
        <div><label>App ID</label><input class="input" name="alipay_app_id" value="<?= htmlspecialchars((string)($settings['alipay_app_id'] ?? '')) ?>"></div>
        <div class="full-row">
          <label>应用私钥</label>
          <textarea class="input" name="alipay_private_key" rows="5" placeholder="可粘贴完整 PEM，或只粘贴 base64 密钥体；留空不修改"></textarea>
          <?php if (!empty($settings['alipay_private_key'])): ?>
            <label class="sub-check"><input type="checkbox" name="alipay_private_key_clear" value="1"> 清空已保存应用私钥</label>
          <?php endif; ?>
        </div>
        <div class="full-row">
          <label>支付宝公钥</label>
          <textarea class="input" name="alipay_public_key" rows="5" placeholder="可粘贴完整 PEM，或只粘贴 base64 密钥体；留空不修改"></textarea>
          <?php if (!empty($settings['alipay_public_key'])): ?>
            <label class="sub-check"><input type="checkbox" name="alipay_public_key_clear" value="1"> 清空已保存支付宝公钥</label>
          <?php endif; ?>
        </div>
      </div>
    </section>



    <section class="settings-panel" data-panel="developer">
      <div class="grid settings-grid">
        <div class="setting-note full-row">
          <strong>开发者商业设置</strong>
          <div class="muted" style="margin-top:6px;">这里控制开发者入驻价格、应用销售分成和提现门槛。支付宝收款通道请在“支付宝支付”Tab 配置。</div>
        </div>
        <div><label>普通开发者权限价格</label><input class="input" type="number" step="0.01" name="developer_join_price" value="<?= htmlspecialchars((string)($settings['developer_join_price'] ?? '99.00')) ?>"></div>
        <div><label>开发者销售分成比例（%）</label><input class="input" type="number" step="0.01" min="0" max="100" name="developer_share_ratio" value="<?= htmlspecialchars((string)($settings['developer_share_ratio'] ?? '70')) ?>"></div>
        <div class="full-row"><label>最低提现金额</label><input class="input" type="number" step="0.01" name="developer_min_withdraw" value="<?= htmlspecialchars((string)($settings['developer_min_withdraw'] ?? '10.00')) ?>"></div>
        <div class="setting-note full-row">
          <div class="muted">公益开发者免费加入，默认只能发布免费应用；普通开发者可发布付费应用并参与分成。</div>
        </div>
      </div>
    </section>


    <div class="settings-actions"><button class="btn" type="submit">保存设置</button></div>
  </form>
</div>

<div class="card smtp-test-card">
  <h3>发送测试邮件</h3>
  <div class="muted" style="margin:6px 0 12px;">先保存 SMTP 配置，再向指定邮箱发送一封测试邮件，用来确认主机、端口、加密方式和授权码是否可用。</div>
  <form method="post" action="/admin.php?path=settings/test-smtp" class="smtp-test-form">
    <?= csrf_field() ?>
    <div><label>测试收件邮箱</label><input class="input" name="test_email" type="email" placeholder="you@example.com" required></div>
    <button class="btn btn-light" type="submit">发送测试邮件</button>
  </form>
</div>

<div class="card">
  <h3>当前预览</h3>
  <div class="grid preview-grid">
    <div><strong>站点名称：</strong><?= htmlspecialchars((string)($settings['site_name'] ?? 'Clay官方站')) ?></div>
    <div><strong>Logo 文字：</strong><?= htmlspecialchars((string)($settings['site_logo_text'] ?? 'Clay官方站')) ?></div>
    <div class="full-row"><strong>首页副标题：</strong><?= htmlspecialchars((string)($settings['site_tagline'] ?? '')) ?></div>
    <div class="full-row"><strong>页脚文案：</strong><?= htmlspecialchars((string)($settings['footer_text'] ?? '')) ?></div>
    <div class="full-row"><strong>用户自助解绑：</strong><?= !empty($settings['user_site_unbind_enabled']) && (string)$settings['user_site_unbind_enabled']==='1' ? '允许' : '关闭' ?></div>
    <div class="full-row"><strong>ClayBBS 购买授权名额：</strong><?= !empty($settings['claybbs_auth_purchase_enabled']) && (string)$settings['claybbs_auth_purchase_enabled']==='1' ? '开启' : '关闭' ?> / 单价 ￥<?= htmlspecialchars(number_format((float)($settings['claybbs_auth_purchase_price'] ?? 0), 2, '.', '')) ?> / 单次最多 <?= (int)($settings['claybbs_auth_purchase_max'] ?? 10) ?> 个</div>
    <div class="full-row"><strong>ClayBBS 申请授权名额：</strong><?= !empty($settings['claybbs_site_limit_request_enabled']) && (string)$settings['claybbs_site_limit_request_enabled']==='1' ? '开启' : '关闭' ?> / 单次最多 <?= (int)($settings['claybbs_site_limit_request_max'] ?? 1) ?> 个</div>
    <div class="full-row"><strong>CUTOT 购买授权名额：</strong><?= !empty($settings['cutot_auth_purchase_enabled']) && (string)$settings['cutot_auth_purchase_enabled']==='1' ? '开启' : '关闭' ?> / 单价 ￥<?= htmlspecialchars(number_format((float)($settings['cutot_auth_purchase_price'] ?? 0), 2, '.', '')) ?> / 单次最多 <?= (int)($settings['cutot_auth_purchase_max'] ?? 10) ?> 个</div>
    <div class="full-row"><strong>CUTOT 申请授权名额：</strong><?= !empty($settings['cutot_site_limit_request_enabled']) && (string)$settings['cutot_site_limit_request_enabled']==='1' ? '开启' : '关闭' ?> / 单次最多 <?= (int)($settings['cutot_site_limit_request_max'] ?? 1) ?> 个</div>
    <div class="full-row"><strong>注册邮箱验证：</strong><?= !empty($settings['email_verify_enabled']) && (string)$settings['email_verify_enabled']==='1' ? '开启' : '关闭' ?></div>
    <div><strong>SMTP 主机：</strong><?= htmlspecialchars((string)($settings['smtp_host'] ?? '')) ?></div>
    <div><strong>SMTP 端口：</strong><?= htmlspecialchars((string)($settings['smtp_port'] ?? '587')) ?> / <?= htmlspecialchars(strtoupper((string)($settings['smtp_secure'] ?? 'tls'))) ?></div>
    <div><strong>发件邮箱：</strong><?= htmlspecialchars((string)($settings['smtp_from_email'] ?? '')) ?></div>
    <div><strong>发件人：</strong><?= htmlspecialchars((string)($settings['smtp_from_name'] ?? '')) ?></div>
    <div class="full-row"><strong>支付宝当面付：</strong><?= !empty($settings['alipay_enabled']) && (string)$settings['alipay_enabled']==='1' ? '开启' : '关闭' ?> / AppID: <?= htmlspecialchars((string)($settings['alipay_app_id'] ?? '')) ?></div>
    <div class="full-row"><strong>支付回调：</strong><code class="break-all"><?= htmlspecialchars($alipayNotifyUrl) ?></code></div>
    <div><strong>普通开发者权限：</strong>￥<?= htmlspecialchars(number_format((float)($settings['developer_join_price'] ?? 99), 2, '.', '')) ?></div>
    <div><strong>开发者分成：</strong><?= htmlspecialchars((string)($settings['developer_share_ratio'] ?? '70')) ?>%</div>
    <div class="full-row"><strong>最低提现：</strong>￥<?= htmlspecialchars(number_format((float)($settings['developer_min_withdraw'] ?? 10), 2, '.', '')) ?></div>
  </div>
</div>

<style>
.settings-tabs{display:flex;gap:8px;flex-wrap:wrap;border-bottom:1px solid var(--line);padding-bottom:12px;margin-bottom:16px}.settings-tab{border:1px solid var(--line);background:#fff;color:#64748b;border-radius:999px;padding:8px 13px;font-weight:700;cursor:pointer}.settings-tab.active{background:#eff6ff;color:#2563eb;border-color:#bfdbfe}.settings-panel{display:none}.settings-panel.active{display:block}.settings-grid,.preview-grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.full-row{grid-column:1 / -1}.product-limit-settings{display:grid;gap:10px}.product-limit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.setting-note,.callback-box{padding:12px;border:1px solid var(--line);border-radius:12px;background:rgba(15,23,42,.02)}.check-label{display:flex;align-items:center;gap:8px;font-weight:700}.sub-check{display:flex;align-items:center;gap:6px;margin-top:8px;color:#64748b;font-size:12px;font-weight:600}.settings-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:14px}.callback-line{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:8px 0}.callback-line code{word-break:break-all}.tiny-btn{padding:6px 10px;border-radius:8px;font-size:12px}.smtp-test-form{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end;}.copy-toast{position:fixed;right:18px;bottom:18px;background:#0f172a;color:#fff;border-radius:10px;padding:9px 12px;font-size:13px;z-index:80;box-shadow:0 10px 30px rgba(15,23,42,.2)}
@media (max-width: 900px){.settings-grid,.preview-grid,.product-limit-grid{grid-template-columns:1fr}.smtp-test-form{grid-template-columns:1fr;}.settings-tab{padding:7px 11px}}
</style>
<script>
(function(){
  const tabs = document.querySelectorAll('.settings-tab');
  const panels = document.querySelectorAll('.settings-panel');
  const key = 'clay-admin-settings-tab';
  function activate(name){
    tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === name));
    panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === name));
    try { localStorage.setItem(key, name); } catch(e) {}
  }
  tabs.forEach(tab => tab.addEventListener('click', () => activate(tab.dataset.tab)));
  let initial = 'base';
  try { initial = localStorage.getItem(key) || initial; } catch(e) {}
  if (!document.querySelector('.settings-tab[data-tab="' + initial + '"]')) initial = 'base';
  activate(initial);
})();
function copyAlipayNotifyUrl(){
  const text = document.getElementById('alipayNotifyUrl')?.innerText || '';
  if (!text) return;
  const done = () => {
    const el = document.createElement('div');
    el.className = 'copy-toast';
    el.textContent = '回调地址已复制';
    document.body.appendChild(el);
    setTimeout(() => el.remove(), 1600);
  };
  if (navigator.clipboard && navigator.clipboard.writeText) navigator.clipboard.writeText(text).then(done).catch(done);
  else { window.prompt('复制回调地址', text); done(); }
}
</script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
