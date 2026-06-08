<?php
$pageTitle = 'ClayGuard 安装教程';
require dirname(__DIR__) . '/layouts/main.php';
?>
<section class="apple-hero clayguard-front-hero">
  <p class="eyebrow">安装指南</p>
  <h1>ClayBBS 加密版安装教程</h1>
  <p>本页说明如何解压正式商用包、启用 ClayGuard 运行组件、获取授权文件并完成安装校验。</p>
  <div class="apple-actions">
    <a class="apple-btn primary" href="#step-1">开始安装</a>
    <a class="apple-btn secondary" href="/index.php?path=me/sites">查看授权</a>
  </div>
</section>

<section class="apple-section clayguard-guide">
  <div class="card" id="step-1">
    <h2>一、安装前准备</h2>
    <div class="table-wrap">
      <table class="table">
        <tbody>
          <tr><th>服务器系统</th><td>Linux x86_64</td></tr>
          <tr><th>PHP 版本</th><td>PHP 8.2 NTS，优先支持宝塔 PHP 8.2</td></tr>
          <tr><th>正式安装包</th><td><code>ClayBBS.zip</code></td></tr>
          <tr><th>运行组件</th><td><code>loaders/linux-x86_64/php-8.2/nts/clayguard.so</code></td></tr>
          <tr><th>授权文件</th><td><code>storage/license/clayguard.lic</code></td></tr>
        </tbody>
      </table>
    </div>
    <p>正式包不内置测试授权文件。安装完成后，系统会为当前域名获取或放入对应的 <code>clayguard.lic</code>。用户也可以登录“我的授权”页面，直接下载对应域名的 <code>clayguard.lic</code>。</p>
  </div>

  <div class="card" id="step-2">
    <h2>二、上传并解压安装包</h2>
    <ol>
      <li>下载官方提供的正式商用 ZIP：<code>ClayBBS.zip</code>。</li>
      <li>上传到服务器网站目录，例如 <code>/www/wwwroot/example.com</code>。</li>
      <li>在网站根目录直接解压。</li>
      <li>确认根目录能看到 <code>index.php</code>、<code>app/</code>、<code>loaders/</code>、<code>CLAYGUARD_DIST.json</code>、<code>CLAYGUARD_MANIFEST.sig</code>。</li>
      <li>不要多套一层外层文件夹，否则访问路径会不正确。</li>
    </ol>
  </div>

  <div class="card" id="step-3">
    <h2>三、启用 ClayGuard 运行组件</h2>
    <p>推荐使用包内一键脚本自动安装：</p>
    <pre><code>bash tools/install-clayguard-loader.sh</code></pre>
    <p>如需手动安装，可参考宝塔 PHP 8.2 示例：</p>
    <pre><code>cd /www/wwwroot/example.com
/www/server/php/82/bin/php-config --extension-dir
cp loaders/linux-x86_64/php-8.2/nts/clayguard.so /www/server/php/82/lib/php/extensions/no-debug-non-zts-20220829/clayguard.so
echo "extension=clayguard.so" >> /www/server/php/82/etc/php.ini
/etc/init.d/php-fpm-82 restart</code></pre>
  </div>

  <div class="card" id="step-4">
    <h2>四、检查是否启用成功</h2>
    <p>在网站根目录执行：</p>
    <pre><code>php tools/clayguard-check.php</code></pre>
    <p>正常输出应包含：</p>
    <pre><code>[OK] clayguard_require available</code></pre>
    <p>如果没有成功，请检查 PHP 版本、扩展目录、<code>php.ini</code> 配置以及 PHP-FPM 是否已重启。</p>
  </div>

  <div class="card" id="step-5">
    <h2>五、验证文件完整性</h2>
    <p>正式包内包含完整性校验信息，用于确认文件没有被篡改或漏传。</p>
    <pre><code>php -d extension=loaders/linux-x86_64/php-8.2/nts/clayguard.so tools/verify-manifest.php .</code></pre>
    <p>正常输出：</p>
    <pre><code>MANIFEST_OK files=... signature=ok</code></pre>
    <p>如果提示文件不匹配，请重新上传完整 ZIP，不要手动修改核心文件。</p>
  </div>

  <div class="card" id="step-6">
    <h2>六、自动获取授权文件</h2>
    <ol>
      <li>在官方站商户后台输入客户授权码和绑定域名，或让客户在“我的授权”页面直接下载对应域名的 <code>clayguard.lic</code>。</li>
      <li>安装向导会自动向官方站请求授权文件。</li>
      <li>系统会保存到 <code>storage/license/clayguard.lic</code>。</li>
      <li>如果需要手动放置，请确保文件名必须是 <code>clayguard.lic</code>。</li>
    </ol>
  </div>

  <div class="card" id="step-7">
    <h2>七、配置网站运行环境</h2>
    <ol>
      <li>确认站点绑定域名正确。</li>
      <li>确认网站运行目录按当前版本要求设置。</li>
      <li>确认 <code>storage/</code>、<code>uploads/</code> 等运行目录可写。</li>
      <li>配置数据库连接。</li>
      <li>配置伪静态；如果暂未配置，可使用 <code>index.php?path=...</code> 兼容访问。</li>
      <li>重启 PHP-FPM 和 Web 服务。</li>
    </ol>
  </div>

  <div class="card" id="step-8">
    <h2>八、首次访问与安装</h2>
    <ol>
      <li>浏览器访问授权绑定的域名。</li>
      <li>如果进入安装页面，按提示填写数据库信息。</li>
      <li>安装完成后进入后台。</li>
      <li>检查首页、后台登录、插件管理、主题管理、应用市场、更新中心。</li>
      <li>如出现域名不一致提示，请确认授权文件对应当前访问域名。</li>
    </ol>
  </div>

  <div class="card" id="step-9">
    <h2>九、常见问题</h2>
    <div class="table-wrap">
      <table class="table">
        <thead><tr><th>提示</th><th>原因</th><th>解决办法</th></tr></thead>
        <tbody>
          <tr><td>运行组件未启用</td><td>没有加载 <code>clayguard.so</code></td><td>检查 PHP 版本、扩展目录、php.ini、PHP-FPM 重启</td></tr>
          <tr><td>找不到授权文件</td><td>授权文件缺失</td><td>上传到 <code>storage/license/clayguard.lic</code></td></tr>
          <tr><td>域名不一致</td><td>授权域名不一致</td><td>使用正确域名访问，或重新申请对应域名授权</td></tr>
          <tr><td>文件校验失败</td><td>文件不完整或被修改</td><td>重新上传官方完整 ZIP</td></tr>
          <tr><td>文件不匹配</td><td>文件被修改或漏传</td><td>重新解压上传，不要修改核心文件</td></tr>
          <tr><td>页面空白或 500</td><td>运行组件、授权或完整性检查异常</td><td>先执行运行组件检测和文件校验</td></tr>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card" id="step-10">
    <h2>十、升级注意事项</h2>
    <ol>
      <li>官方每次重新生成安装包都会有新的版本标识和签名。</li>
      <li>不要混用不同版本的程序文件、运行组件和校验文件。</li>
      <li>升级时请整体上传同一个 ZIP 内的文件。</li>
      <li>客户授权文件通常可以继续使用，除非域名变更或授权过期。</li>
      <li>升级后重新执行运行组件检测和文件校验。</li>
    </ol>
  </div>
</section>

<style>
.clayguard-front-hero{background:radial-gradient(circle at 12% 20%,rgba(83,58,253,.16),transparent 30%),linear-gradient(135deg,#fff,#f7f9ff 58%,#eef0ff)!important}.clayguard-front-hero .eyebrow{text-transform:uppercase;letter-spacing:.12em;font-size:12px;font-weight:800;color:var(--primary);margin:0 0 8px}.clayguard-guide{display:grid;gap:16px;min-width:0}.clayguard-guide .card{background:#fff;border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow-soft);padding:22px;min-width:0;max-width:100%;overflow:hidden}.clayguard-guide h2{margin-top:0;font-weight:400;letter-spacing:-.02em}.clayguard-guide p,.clayguard-guide li{line-height:1.85;color:var(--text);overflow-wrap:anywhere;word-break:break-word}.clayguard-guide ol{padding-left:24px}.clayguard-guide code{background:#f1f5ff;border:1px solid var(--line);border-radius:4px;padding:2px 5px;color:#3226b8;max-width:100%;overflow-wrap:anywhere;word-break:break-word}.clayguard-guide .table-wrap{max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}.clayguard-guide .table{width:100%;min-width:680px;table-layout:auto}.clayguard-guide .table th,.clayguard-guide .table td{text-align:left;white-space:normal;overflow-wrap:anywhere;word-break:break-word}.clayguard-guide pre{white-space:pre-wrap;overflow-wrap:anywhere;word-break:break-word;max-width:100%;background:#0f172a;color:#e2e8f0;padding:16px;border-radius:8px;overflow:auto}.clayguard-guide pre code{background:transparent;border:0;color:inherit;padding:0;white-space:pre-wrap;display:block;overflow-wrap:anywhere;word-break:break-word}.clayguard-front-hero{overflow:hidden}.clayguard-guide .card *{min-width:0}@media (max-width: 900px){.clayguard-guide .card{padding:18px}.clayguard-guide .table{min-width:620px}}@media (max-width: 760px){.clayguard-front-hero{padding:20px 16px!important}.clayguard-guide{gap:12px}.clayguard-guide .card{padding:16px;border-radius:8px}.clayguard-guide ol{padding-left:20px}.clayguard-guide pre{padding:12px;border-radius:6px}.clayguard-guide .table{min-width:560px}}
</style>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
