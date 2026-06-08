<?php
$pageTitle = 'ClayGuard 安装教程';
require dirname(__DIR__) . '/layouts/main.php';
?>
<div class="admin-page clayguard-guide-page">
  <div class="card apple-hero" style="background:linear-gradient(135deg,#fff,#f7f9ff)!important">
    <div>
      <p class="eyebrow">ClayGuard Loader</p>
      <h2>ClayBBS 加密版安装教程</h2>
      <p style="color:var(--muted);line-height:1.8;max-width:900px">本教程用于安装官方分发的 ClayBBS 加密商用包。加密包内核心授权、市场、更新与安装逻辑已由 ClayGuard 保护；插件、主题、前端资源仍保持正常开发方式。</p>
    </div>
  </div>

  <div class="card">
    <h3>一、安装前准备</h3>
    <div class="table-wrap">
      <table class="table">
        <tbody>
          <tr><th>服务器系统</th><td>Linux x86_64</td></tr>
          <tr><th>PHP 版本</th><td>PHP 8.2 NTS，宝塔环境优先支持</td></tr>
          <tr><th>正式安装包</th><td><code>ClayBBS.zip</code></td></tr>
          <tr><th>必要权限</th><td>需要能上传站点文件、修改 PHP 扩展配置、重启 PHP-FPM</td></tr>
          <tr><th>授权文件</th><td>从官方站为客户域名生成的 <code>clayguard.lic</code></td></tr>
          <tr><th>Loader 文件</th><td>加密包内 <code>loaders/linux-x86_64/php-8.2/nts/clayguard.so</code></td></tr>
        </tbody>
      </table>
    </div>
    <p style="color:var(--muted)">注意：正式商用 ZIP 不内置测试授权，必须单独上传对应域名的 <code>storage/license/clayguard.lic</code>。</p>
  </div>

  <div class="card">
    <h3>二、上传并解压程序包</h3>
    <ol class="guide-steps">
      <li>在官方站后台下载或上传正式商用 ZIP，例如 <code>ClayBBS.zip</code>。</li>
      <li>将 ZIP 上传到客户服务器站点目录，例如 <code>/www/wwwroot/example.com</code>。</li>
      <li>解压 ZIP 后，将包内文件放到站点根目录。</li>
      <li>确认站点根目录可以看到 <code>index.php</code>、<code>app/</code>、<code>loaders/</code>、<code>CLAYGUARD_DIST.json</code>、<code>CLAYGUARD_MANIFEST.sig</code>。</li>
      <li>确认没有把 ZIP 外层目录作为网站根目录。如果访问异常，通常是多套了一层目录。</li>
    </ol>
  </div>

  <div class="card">
    <h3>三、安装 ClayGuard Loader</h3>
    <p>建议直接使用包内一键脚本：</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>bash tools/install-clayguard-loader.sh</code></pre>
    <p style="color:var(--muted)">如需手动安装，可按宝塔 PHP 8.2 环境执行：</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>cd /www/wwwroot/example.com
/www/server/php/82/bin/php-config --extension-dir
cp loaders/linux-x86_64/php-8.2/nts/clayguard.so /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/clayguard.so
echo "extension=clayguard.so" >> /www/server/php/82/etc/php.ini
/etc/init.d/php-fpm-82 restart</code></pre>
  </div>

  <div class="card">
    <h3>四、检查 Loader 是否加载成功</h3>
    <p>在站点根目录执行：</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>php tools/clayguard-check.php</code></pre>
    <p>正常结果应看到类似：</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>[OK] clayguard_require available</code></pre>
    <p style="color:var(--muted)">如果提示 Loader not installed，请检查 PHP CLI 和 PHP-FPM 是否使用同一个 PHP 版本，并确认 <code>php.ini</code> 中已加入扩展。</p>
  </div>

  <div class="card">
    <h3>五、上传授权文件</h3>
    <ol class="guide-steps">
      <li>在官方站后台为客户域名生成授权文件。</li>
      <li>下载授权文件，文件名保持为 <code>clayguard.lic</code>。</li>
      <li>上传到客户站点：</li>
    </ol>
    <p style="color:var(--muted)">如果你使用官方站商户后台，也可以直接自动获取授权文件并写入 <code>storage/license/clayguard.lic</code>；用户也可在“我的授权”页面下载对应域名的授权文件。</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>/www/wwwroot/example.com/storage/license/clayguard.lic</code></pre>
    <p>如果目录不存在，先创建：</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>mkdir -p /www/wwwroot/example.com/storage/license</code></pre>
  </div>

  <div class="card">
    <h3>六、验证分发包完整性</h3>
    <p>正式包内包含 <code>CLAYGUARD_DIST.json</code> 和 <code>CLAYGUARD_MANIFEST.sig</code>，用于防止文件被篡改。</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>php -d extension=loaders/linux-x86_64/php-8.2/nts/clayguard.so tools/verify-manifest.php .</code></pre>
    <p>正常结果：</p>
    <pre style="white-space:pre-wrap;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto"><code>MANIFEST_OK files=... signature=ok</code></pre>
    <p style="color:var(--muted)">如果出现 Hash mismatch 或 Manifest signature invalid，说明程序文件被改动、上传不完整或使用了错误版本的文件。</p>
  </div>

  <div class="card">
    <h3>七、配置网站运行环境</h3>
    <ol class="guide-steps">
      <li>站点运行目录按当前版本要求设置。</li>
      <li>确保 <code>storage/</code>、<code>uploads/</code> 等运行目录可写。</li>
      <li>配置数据库连接文件。</li>
      <li>配置伪静态规则；未配置时可使用 <code>index.php?path=...</code> 兼容访问。</li>
      <li>重启 PHP-FPM 和 Web 服务。</li>
    </ol>
  </div>

  <div class="card">
    <h3>八、首次访问与安装</h3>
    <ol class="guide-steps">
      <li>浏览器访问客户域名。</li>
      <li>如果进入安装页面，按页面提示填写数据库信息。</li>
      <li>安装完成后检查首页、后台登录、应用市场、插件管理、主题管理、更新中心。</li>
      <li>如果提示 <code>domain mismatch</code>，说明授权文件绑定域名和当前访问域名不一致。</li>
      <li>如果提示 <code>ClayGuard manifest invalid</code>，说明分发文件被改动或上传不完整。</li>
    </ol>
  </div>

  <div class="card">
    <h3>九、常见问题处理</h3>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>问题</th><th>原因</th><th>处理方式</th></tr></thead>
        <tbody>
          <tr><td>ClayGuard Loader not installed</td><td>PHP 未加载 <code>clayguard.so</code></td><td>检查 <code>php.ini</code>，重启 PHP-FPM，确认 PHP 版本为 8.2 NTS</td></tr>
          <tr><td>license file not found</td><td>授权文件未上传</td><td>上传到 <code>storage/license/clayguard.lic</code></td></tr>
          <tr><td>domain mismatch</td><td>授权绑定域名不一致</td><td>在官方站重新为正确域名生成授权</td></tr>
          <tr><td>Manifest signature invalid</td><td>manifest 或签名文件不匹配</td><td>重新上传完整 ZIP，不要手动修改核心文件</td></tr>
          <tr><td>Hash mismatch</td><td>文件被修改或上传缺失</td><td>重新解压上传正式包</td></tr>
          <tr><td>后台/市场相关页面报错</td><td>加密核心文件未正确加载</td><td>先检查 Loader、授权文件和 manifest 校验</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card">
    <h3>十、升级与重新分发</h3>
    <ol class="guide-steps">
      <li>每次官方重新生成加密包，都会有新的 <code>build_id</code> 和签名。</li>
      <li>不要混用不同版本的 <code>.clay</code>、stub、Loader、manifest。</li>
      <li>升级时应整体上传同一个 ZIP 内的文件。</li>
      <li>客户授权文件 <code>storage/license/clayguard.lic</code> 可按域名继续使用，除非授权过期或域名变化。</li>
      <li>升级后再次执行 manifest 校验和 Loader 检测。</li>
    </ol>
  </div>
</div>
<style>
.clayguard-guide-page .guide-steps{line-height:1.9;color:var(--text);padding-left:24px}.clayguard-guide-page code{background:#f1f5ff;border:1px solid var(--line);border-radius:4px;padding:2px 5px;color:#3226b8}.clayguard-guide-page pre code{background:transparent;border:0;color:inherit;padding:0}.clayguard-guide-page h3{margin-top:0;font-size:22px}.clayguard-guide-page .eyebrow{text-transform:uppercase;letter-spacing:.12em;font-size:12px;font-weight:800;color:var(--primary);margin:0 0 8px}
</style>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
