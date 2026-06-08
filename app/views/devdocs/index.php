<?php
$pageTitle='开发文档 - Clay官方站';
require dirname(__DIR__) . '/layouts/main.php';
$docTypeRaw = (string)($_GET['type'] ?? 'plugin');
$docType = in_array($docTypeRaw, ['plugin','theme'], true) ? $docTypeRaw : 'plugin';
?>
<style>
.wiki-hero{padding:30px;border-radius:24px;background:radial-gradient(circle at 12% 20%,rgba(37,99,235,.15),transparent 28%),linear-gradient(135deg,#f8fbff,#fff 58%,#ecfeff);border-color:#dbeafe}.wiki-hero h1{font-size:42px;letter-spacing:-.055em;line-height:1.1;margin:12px 0 8px}.wiki-hero p{max-width:860px;line-height:1.85}.wiki-shell{display:grid;grid-template-columns:280px minmax(0,1fr);gap:18px;align-items:start}.wiki-side{position:sticky;top:78px;padding:14px;border-radius:20px}.wiki-switch{display:grid;grid-template-columns:1fr 1fr;gap:6px;padding:5px;border:1px solid #e2e8f0;border-radius:14px;background:#f8fafc;margin-bottom:12px}.wiki-switch a{height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;text-decoration:none;color:#64748b;font-size:13px;font-weight:950}.wiki-switch a.active{background:#fff;color:#2563eb;box-shadow:0 8px 20px rgba(15,23,42,.08)}.wiki-nav-title{font-size:12px;color:#94a3b8;font-weight:950;margin:12px 8px 6px}.wiki-nav a{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:10px;color:#64748b;text-decoration:none;font-size:13px;font-weight:800}.wiki-nav a:hover{background:#eff6ff;color:#2563eb}.wiki-main{display:grid;gap:14px}.wiki-doc{display:none}.wiki-doc.active{display:grid;gap:14px}.wiki-section{border-radius:20px}.wiki-section h2{font-size:25px;letter-spacing:-.03em;margin:0 0 10px;padding-bottom:10px;border-bottom:1px solid #e2e8f0}.wiki-section h3{font-size:17px;margin:18px 0 8px}.wiki-section p,.wiki-section li{line-height:1.85;color:#475569;font-size:14px}.wiki-section ul,.wiki-section ol{padding-left:20px}.codebox{background:#0f172a;color:#e2e8f0;border-radius:12px;padding:14px;overflow:auto;font-size:13px;line-height:1.65;margin:10px 0}.wiki-note{background:#fff7ed;border:1px solid #fed7aa;color:#9a3412;border-radius:14px;padding:12px 14px;line-height:1.7}.wiki-download{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;background:#f8fafc;border:1px solid #e2e8f0;border-radius:14px;padding:14px}.wiki-download strong{display:block;margin-bottom:4px}.tag{display:inline-flex;border-radius:999px;background:#dbeafe;color:#1d4ed8;font-size:12px;font-weight:900;padding:5px 9px;margin-right:6px}.muted{color:#64748b}@media(max-width:900px){.wiki-shell{display:block}.wiki-side{position:static;margin-bottom:14px}.wiki-download{grid-template-columns:1fr}.wiki-download .btn{width:100%;justify-content:center}}


.wiki-hero,.wiki-shell,.wiki-main,.wiki-doc,.wiki-section{max-width:100%;min-width:0}.wiki-hero code,.wiki-section code{overflow-wrap:anywhere}.wiki-section table{min-width:720px}.wiki-section>div[style*="overflow-x:auto"]{max-width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}.codebox{max-width:100%;white-space:pre;overflow-x:auto}@media(max-width:900px){.wiki-shell{display:block!important}.wiki-side{position:relative!important;top:auto!important;margin-bottom:12px}.wiki-nav{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px}.wiki-nav a{flex:0 0 auto;white-space:nowrap}.wiki-main,.wiki-doc{display:block!important}.wiki-section{margin:12px 0!important;padding:16px!important}.wiki-download{display:block!important}.wiki-download .btn{margin-top:10px;width:100%}.wiki-hero{padding:18px!important}.wiki-hero h1{font-size:30px!important}.wiki-hero p{overflow-wrap:anywhere}}
</style>
<section class="card wiki-hero">
  <span class="tag">Developer Wiki</span><span class="tag">Plugin</span><span class="tag">Theme</span><span class="tag">API v1.0.0</span>
  <h1>面向生态开发者的工程文档</h1>
  <p class="muted">当前 Extension API 版本 <strong>1.0.0</strong>，核心最低要求 <strong>1.0.0</strong>。从插件、主题到授权、打包与安全规范，帮助开发者以官方兼容方式扩展 ClayBBS。文档公开阅读；示例工程仅开发者账号可下载。</p>
  <p class="muted" style="margin-top:8px;">📖 论坛端完整 API 参考：<code>app/Extension/ExtensionContract.php</code> · 打包规范：<code>docs/extension-manifest-spec.md</code> · API 文档：<code>docs/extension-api.md</code></p>
</section>

<div class="wiki-shell">
  <aside class="card wiki-side">
    <div class="wiki-switch">
      <a class="<?= $docType==='plugin'?'active':'' ?>" href="/index.php?path=devdocs&type=plugin">插件</a>
      <a class="<?= $docType==='theme'?'active':'' ?>" href="/index.php?path=devdocs&type=theme">主题</a>
    </div>
    <?php if ($docType === 'plugin'): ?>
      <div class="wiki-nav-title">插件开发</div>
      <nav class="wiki-nav"><a href="#plugin-overview">开发模型</a><a href="#plugin-naming">命名规范</a><a href="#plugin-structure">目录结构</a><a href="#plugin-manifest">配置文件</a><a href="#plugin-dependencies">依赖与版本</a><a href="#plugin-lifecycle">生命周期</a><a href="#plugin-apiref">API 方法速查</a><a href="#plugin-bootstrap-file">bootstrap.php</a><a href="#plugin-hooks">Hook 与过滤器</a><a href="#plugin-admin">后台页面</a><a href="#plugin-db-perm">数据库与权限</a><a href="#plugin-license">授权与付费</a><a href="#plugin-security">安全清单</a><a href="#plugin-package">打包发布</a><a href="#plugin-example">示例工程</a></nav>
    <?php else: ?>
      <div class="wiki-nav-title">主题开发</div>
      <nav class="wiki-nav"><a href="#theme-overview">开发模型</a><a href="#theme-naming">命名规范</a><a href="#theme-structure">目录结构</a><a href="#theme-manifest">配置文件</a><a href="#theme-api">ThemeApi</a><a href="#theme-override">视图覆盖</a><a href="#theme-assets">资源组织</a><a href="#theme-style">样式规范</a><a href="#theme-compat">兼容与表单</a><a href="#theme-license">授权与付费主题</a><a href="#theme-package">打包发布</a><a href="#theme-example">示例工程</a></nav>
    <?php endif; ?>
  </aside>

  <main class="wiki-main">
    <?php if ($docType === 'plugin'): ?>
    <article class="wiki-doc active">
      <section class="card wiki-section" id="plugin-overview"><h2>插件开发模型</h2><p>插件用于扩展 ClayBBS 的业务能力，例如新增后台页面、监听 Hook、扩展通知、接入外部服务、增加内容处理逻辑等。插件启用后，系统会加载插件目录中的 <code>bootstrap.php</code>。</p><div class="wiki-note"><strong>原则：</strong>插件可以增强业务，但不能破坏主系统数据结构；涉及订单、钱包、授权、用户权限的逻辑必须有服务端校验和日志。</div></section>
      <section class="card wiki-section" id="plugin-naming"><h2>插件命名规范</h2><ul><li><strong>slug：</strong>只允许小写英文、数字、下划线、短横线，例如 <code>hello_notice</code>。</li><li><strong>命名空间：</strong>建议使用 <code>Plugin\你的Slug</code> 或避免声明命名空间，防止和主系统类冲突。</li><li><strong>数据库表：</strong>建议使用 <code>plugin_插件slug_业务名</code>，例如 <code>plugin_hello_notice_logs</code>。</li><li><strong>配置键：</strong>建议使用 <code>plugin_插件slug_配置名</code>，避免污染全局设置。</li><li><strong>版本号：</strong>使用语义化版本，修复补丁递增第三位，新增兼容能力递增第二位，破坏性变化递增第一位。</li></ul></section>
      <section class="card wiki-section" id="plugin-structure"><h2>插件目录结构</h2><pre class="codebox">hello_notice/
├── market.json
├── plugin.json
├── bootstrap.php
├── migrations/
│   └── 202605070001_create_table.sql
├── views/
│   └── admin/index.php
├── assets/
│   ├── css/plugin.css
│   └── js/plugin.js
└── README.md</pre><ul><li><code>market.json</code>：市场识别文件，提交官方市场时必须提供。</li><li><code>plugin.json</code>：论坛端插件识别文件。</li><li><code>bootstrap.php</code>：插件启用后的入口。</li><li><code>migrations/</code>：数据库迁移文件，建议可重复执行。</li></ul></section>
      <section class="card wiki-section" id="plugin-manifest"><h2>plugin.json / market.json</h2><pre class="codebox">{
  "type": "plugin",
  "slug": "hello_notice",
  "name": "Hello Notice",
  "version": "1.0.0",
  "api_version": "1.0.0",
  "min_core_version": "1.0.0",
  "description": "一个插件开发示例",
  "author": "你的名字",
  "dependencies": {
    "php": ">=8.0",
    "claybbs": ">=1.0.0"
  }
}</pre><p><code>slug</code> 只能使用英文、数字、下划线和短横线。上线后不要频繁变更 slug，否则会影响升级、授权识别和用户迁移。</p><p><code>api_version</code> 声明插件依赖的 Extension API 版本，论坛加载时会检查兼容性。不声明则按兼容处理；声明高于当前 API 时无法启用。</p><p><code>min_core_version</code> 是给开发者和市场展示的核心版本说明；论坛实际依赖检查以 <code>dependencies</code> / <code>requires</code> 为准。</p><p><code>market.json</code> 同样包含这些字段，但用于提交官方市场识别；<code>plugin.json</code> 用于论坛本地识别。市场包可以把 <code>market.json</code> 放在压缩包根目录，也可以放在 <code>slug/market.json</code>。</p></section>
      <section class="card wiki-section" id="plugin-dependencies"><h2>依赖与版本检查</h2><p>论坛端 <code>PluginManager</code> 会读取 <code>dependencies</code>，同时兼容旧字段 <code>requires</code>。依赖支持三类：</p><ul><li><strong>PHP：</strong><code>"php": ">=8.0"</code>，会与当前 PHP 版本比较。</li><li><strong>ClayBBS/Core：</strong><code>"claybbs": ">=1.0.0"</code> 或 <code>"core": ">=1.0.0"</code>，会与论坛核心版本比较。</li><li><strong>其他插件：</strong><code>"other_plugin": ">=1.2.0"</code>，要求目标插件已安装、已启用且版本满足。</li></ul><pre class="codebox">"dependencies": {
  "php": ">=8.0",
  "claybbs": ">=1.0.0",
  "wallet_plus": ">=1.2.0"
}</pre><div class="wiki-note"><strong>建议：</strong>新插件优先使用 <code>dependencies</code>；不要把运行环境依赖写成普通文本说明，否则后台无法自动拦截不兼容安装。</div></section>
      <section class="card wiki-section" id="plugin-lifecycle"><h2>插件生命周期</h2><ol><li><strong>安装：</strong>官方市场包下载到论坛端，校验类型和 slug 后解压到 <code>plugins/slug</code>；若原目录存在，会先备份到 <code>storage/backups/plugins/</code>。</li><li><strong>迁移：</strong>安装后自动执行 <code>install.sql</code>、<code>database/install.sql</code> 和 <code>migrations/*.sql</code>，并记录到 <code>extension_migrations</code>，相同文件路径和 hash 不会重复执行。</li><li><strong>启用：</strong>后台开启插件，先检查依赖、授权和 API 版本，通过后把 slug 写入 <code>plugins_enabled</code> 设置。</li><li><strong>启动：</strong>每次请求启动时，系统加载已启用插件的 <code>bootstrap.php</code>；启动异常会写入 <code>extension_error_logs</code>，不会拖垮主系统。</li><li><strong>运行：</strong>插件通过 Hook、PluginApi、服务类、模板或后台入口扩展能力。</li><li><strong>停用：</strong>插件不再加载 <code>bootstrap.php</code>，但数据通常保留。</li><li><strong>卸载：</strong>删除插件目录；如需删除数据，应由插件提供明确迁移/卸载说明，不建议默认清库。</li></ol></section>
      <section class="card wiki-section" id="plugin-apiref"><h2>PluginApi 方法速查</h2><p>所有插件必须通过 <code>App\Extension\PluginApi</code> 与论坛核心交互。建议统一别名：<code>use App\Extension\PluginApi as ClayPlugin;</code></p>
<div style="overflow-x:auto">
<table style="width:100%;border-collapse:collapse;font-size:13px">
<thead><tr style="background:#f1f5f9"><th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e2e8f0">分类</th><th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e2e8f0">方法</th><th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e2e8f0">说明</th></tr></thead>
<tbody>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">系统</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>version()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">返回 API 版本号</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">系统</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>siteUrl()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">获取站点根 URL，用于拼接回调/绝对路径</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">系统</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>assetUrl(slug, path)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">获取插件静态资源 URL</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">钩子</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>listen(hook, callback, priority)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">监听系统钩子</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">钩子</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>fire(hook, payload)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">主动触发事件</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">钩子</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>filter(hook, value, context)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">过滤值（类似 apply_filters）</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">路由</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>get(path, handler)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">注册 GET 路由</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">路由</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>post(path, handler)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">注册 POST 路由</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">UI</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>adminMenu(html, group, priority)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">后台侧边栏菜单项</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">UI</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>userQuickAction(html, priority)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">用户中心快捷操作</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">UI</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>appendStyles(html, priority)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">注入 CSS/JS 到前台 head</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">数据库</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>db()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">获取 PDO 数据库连接</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">设置</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>setting(key, default)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">读取站点全局设置</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">设置</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>setSetting(key, value)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">写入站点全局设置</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">设置</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>pluginSetting(slug, key, default)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">读取插件私有设置</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">设置</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>setPluginSetting(slug, key, value)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">写入插件私有设置</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">安全</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>csrfField()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">输出 CSRF 隐藏字段</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">安全</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>csrfVerify()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">校验 CSRF token</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">用户</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>currentUser()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">获取当前登录用户信息</td></tr>
<tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9;font-weight:700">工具</td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>e(value)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">HTML 转义输出</td></tr>
</tbody>
</table>
</div>
<p style="margin-top:10px">方法详情见源码 PHPDoc 或 <code>app/Extension/PluginApi.php</code>。</p></section>

      <section class="card wiki-section" id="plugin-bootstrap-file"><h2>bootstrap.php 接入</h2><p><code>bootstrap.php</code> 会随请求加载，必须保持轻量。推荐只注册路由、菜单、样式和 Hook，不要在这里执行耗时任务、远程请求或大量数据库扫描。</p><pre class="codebox">&lt;?php

use App\Extension\PluginApi as ClayPlugin;

ClayPlugin::listen('web.routes', function (array $payload): array {
    ClayPlugin::get('/hello-notice', [HelloNoticeController::class, 'index']);
    return $payload;
});

ClayPlugin::listen('admin.routes', function (array $payload): array {
    ClayPlugin::get('/admin/hello-notice', [AdminController::class, 'index']);
    ClayPlugin::post('/admin/hello-notice/settings', [AdminController::class, 'save']);
    return $payload;
});

ClayPlugin::adminMenu('&lt;a href="/admin.php?path=hello-notice" class="menu-link"&gt;Hello Notice&lt;/a&gt;');
ClayPlugin::appendStyles('&lt;link rel="stylesheet" href="' . ClayPlugin::assetUrl('hello_notice', 'assets/css/plugin.css') . '"&gt;');</pre><div class="wiki-note"><strong>注意：</strong><code>ClayPlugin::get()</code> / <code>post()</code> 必须在 <code>web.routes</code> 或 <code>admin.routes</code> 回调内调用；否则当前没有 Router 实例，会抛出异常。</div></section>
      <section class="card wiki-section" id="plugin-hooks"><h2>Hook 与过滤器</h2><p>使用 PluginApi 监听事件和过滤数据：</p><pre class="codebox">use App\Extension\PluginApi as ClayPlugin;

ClayPlugin::listen('thread.created', function (array $payload) {
    $thread = $payload['thread'];
    // 新帖子发布后的处理逻辑
    return $payload;
});

$filtered = ClayPlugin::filter('thread.title', $title, ['thread_id' => 1]);</pre><p>主动触发事件（广播给其他插件）：</p><pre class="codebox">ClayPlugin::fire('my_plugin.event', ['data' => $someData]);</pre><p>插件应优先通过 Hook 接入，不要直接修改主系统文件。若当前 Hook 不够，应在插件文档中说明需要新增的 Hook 点，再由官方评估加入主系统。</p></section>
      <section class="card wiki-section" id="plugin-admin"><h2>后台页面开发</h2><p>插件如果需要后台页面，建议遵守主系统后台风格：表格、表单、分区清晰，不要做大卡片堆叠。</p><pre class="codebox">// 示例思路：bootstrap.php 中注册你的菜单/入口，后台控制器中必须做权限校验
use App\Middleware\AdminAuth;
use App\Middleware\Permission;

AdminAuth::check();
Permission::require('admin.settings');</pre><ul><li>所有 POST 操作必须调用 <code>csrf_verify()</code>。</li><li>删除、回滚、批量处理等高风险动作必须二次确认。</li><li>后台页面不要泄露密钥、token、授权码原文。</li></ul></section>
      <section class="card wiki-section" id="plugin-db-perm"><h2>数据库与权限</h2><ul><li>建表 SQL 可放在 <code>install.sql</code>、<code>database/install.sql</code> 或 <code>migrations/*.sql</code>；论坛会按自然排序执行迁移。</li><li>迁移 SQL 必须可重复执行，建议使用 <code>CREATE TABLE IF NOT EXISTS</code>、幂等插入或唯一键保护。</li><li>迁移安全限制会拦截 <code>DROP/CREATE/ALTER DATABASE</code>、用户权限、<code>GRANT</code>、<code>LOAD DATA</code>、<code>INTO OUTFILE</code>、<code>SET GLOBAL</code>、<code>SHUTDOWN</code> 等危险语句。</li><li>插件表建议使用 <code>plugin_slug_xxx</code> 命名，不要直接改系统表结构。</li><li>后台入口必须检查管理员权限，不要只靠前端隐藏。</li><li>涉及钱包、订单、授权、用户状态的操作必须服务端二次校验并写日志。</li><li>危险操作必须有 CSRF 和确认流程。</li></ul></section>
      <section class="card wiki-section" id="plugin-license"><h2>授权与付费入口保护</h2><p>付费插件必须声明授权要求和受保护入口。官方站在已购买且绑定域名后，会在下载包根目录注入 <code>license.json</code> / <code>market-license.json</code>；论坛端会使用官方公钥校验签名和域名。</p><pre class="codebox">{
  "type": "plugin",
  "slug": "hello_notice",
  "name": "Hello Notice",
  "version": "1.0.0",
  "license": {
    "required": true,
    "protected_routes": ["/admin/hello-notice"],
    "protected_hooks": ["admin.menu.plugins"],
    "protected_features": ["admin_page", "frontend_display"]
  }
}</pre><p>插件代码中应在关键入口调用统一授权网关：</p><pre class="codebox">use App\Services\MarketLicenseService;

MarketLicenseService::guard('plugin', 'hello_notice', 'admin_page');

if (!MarketLicenseService::featureAllowed('plugin', 'hello_notice', 'frontend_display')) {
    return $payload;
}</pre><ul><li>未授权或域名不匹配时，论坛不会启用付费插件。</li><li>菜单、Hook 输出、后台控制器、前台路由都应按 feature 做保护。</li><li>破解包即使被手动复制，也无法通过官方更新接口下载新版本。</li><li>PHP 源码无法绝对防破解，但入口网关 + 官方更新授权能提高破解成本。</li></ul></section>
      <section class="card wiki-section" id="plugin-security"><h2>插件安全清单</h2><ul><li>所有数据库写入使用预处理，不拼接用户输入。</li><li>上传文件必须限制 MIME、扩展名、大小和真实内容。</li><li>不要使用 <code>eval</code>、动态 include 用户路径、反序列化不可信数据。</li><li>远程请求设置超时，并处理失败。</li><li>不要在插件包内放置私钥、数据库密码、授权 token。</li><li>不要修改系统用户、钱包、订单、授权状态，除非有明确权限和日志。</li></ul></section>
      <section class="card wiki-section" id="plugin-package"><h2>插件打包发布</h2><ol><li>复制示例工程并修改 slug/name/version/api_version/dependencies。</li><li>本地安装、启用、禁用、升级、回滚测试；确认迁移 SQL 不会重复破坏数据。</li><li>测试管理员、普通用户、未登录用户三种状态。</li><li>确认不包含 <code>.git</code>、日志、上传文件、缓存、私钥、数据库配置、<code>license.json</code>、<code>market-license.json</code> 等运行文件。</li><li>压缩插件根目录；市场安装器支持 <code>market.json</code> 在 zip 根目录或 <code>slug/market.json</code>，但解压后插件根目录必须包含 <code>plugin.json</code>。</li><li>到开发者中心创建插件并提交审核。</li></ol><div class="wiki-note">公益开发者只能发布免费插件；普通开发者可以发布免费或付费插件。付费插件授权文件由官方站按购买和绑定域名动态注入，开发者不要把授权文件打进分发包。</div></section>
      <section class="card wiki-section" id="plugin-example"><h2>插件示例工程</h2><div class="wiki-download"><div><strong>Hello Notice 示例插件</strong><p class="muted">包含 market.json、plugin.json、api_version 声明、bootstrap.php（路由注册 + 后台菜单 + 样式注入）和 README。</p></div><?php if($canDownloadExamples): ?><a class="btn" href="/index.php?path=devdocs/download&id=plugin-hello-notice">下载插件工程</a><?php else: ?><a class="btn btn-light" href="/index.php?path=developer">成为开发者后下载</a><?php endif; ?></div></section>
    </article>
    <?php else: ?>
    <article class="wiki-doc active">
      <section class="card wiki-section" id="theme-overview"><h2>主题开发模型</h2><p>主题用于替换 ClayBBS 的展示层。论坛控制器通过 <code>theme_view('web/...')</code> 查找视图：当前主题存在同路径文件时优先使用主题文件，找不到时回退默认视图。</p><div class="wiki-note"><strong>原则：</strong>主题主要负责展示，不建议在主题中写复杂业务逻辑，也不要改变表单字段语义。付费主题授权无效时，论坛会自动回退默认主题。</div></section>
      <section class="card wiki-section" id="theme-naming"><h2>主题命名规范</h2><ul><li><strong>slug：</strong>使用稳定英文标识，例如 <code>clay_light</code>。</li><li><strong>视图路径：</strong>必须和默认视图相同路径，才能完成覆盖。</li><li><strong>资源路径：</strong>建议统一放在主题 <code>assets/</code> 下，不要污染主系统 <code>assets/</code>。</li><li><strong>版本：</strong>主题升级应保持同一 slug，并递增版本号。</li></ul></section>
      <section class="card wiki-section" id="theme-structure"><h2>主题目录结构</h2><pre class="codebox">clay_light/
├── market.json
├── theme.json
├── views/
│   ├── web/home/index.php
│   ├── web/thread/show.php
│   └── web/layouts/main.php
├── assets/
│   ├── css/theme.css
│   └── js/theme.js
└── README.md</pre><ul><li><code>theme.json</code>：论坛端主题识别文件。</li><li><code>views/</code>：按默认视图路径覆盖页面。</li><li><code>assets/</code>：主题自己的 CSS、JS、图片资源。</li></ul></section>
      <section class="card wiki-section" id="theme-manifest"><h2>theme.json / market.json</h2><pre class="codebox">{
  "type": "theme",
  "slug": "clay_light",
  "name": "Clay Light",
  "version": "1.0.0",
  "api_version": "1.0.0",
  "min_core_version": "1.0.0",
  "description": "一个主题开发示例",
  "author": "你的名字"
}</pre><p>主题 slug 同样要求稳定。用户安装后，升级包应沿用同一个 slug。</p><p><code>api_version</code> 声明主题依赖的 Extension API 版本，论坛切换主题时会检查兼容性。</p><p><code>market.json</code> 用于提交官方市场；<code>theme.json</code> 用于论坛本地识别。</p></section>
      <section class="card wiki-section" id="theme-api"><h2>ThemeApi 方法速查</h2><p>主题可通过 <code>App\Extension\ThemeApi</code> 获取当前主题、视图路径、资源 URL 和安全转义。</p><div style="overflow-x:auto"><table style="width:100%;border-collapse:collapse;font-size:13px"><thead><tr style="background:#f1f5f9"><th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e2e8f0">方法</th><th style="padding:8px 12px;text-align:left;border-bottom:1px solid #e2e8f0">说明</th></tr></thead><tbody><tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>version()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">返回 ThemeApi 版本号</td></tr><tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>active()</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">返回当前启用主题 slug；授权失效会返回 default</td></tr><tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>view(path)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">按当前主题解析视图路径</td></tr><tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>assetUrl(path, slug)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">生成主题资源 URL；默认主题时返回站点根相对路径</td></tr><tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>cssTag(path, slug)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">生成主题 CSS link 标签</td></tr><tr><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9"><code>e(value)</code></td><td style="padding:6px 12px;border-bottom:1px solid #f1f5f9">HTML 转义</td></tr></tbody></table></div><pre class="codebox">use App\Extension\ThemeApi as ClayTheme;

&lt;link rel="stylesheet" href="&lt;?= ClayTheme::e(ClayTheme::assetUrl('assets/css/theme.css')) ?&gt;"&gt;
&lt;img src="&lt;?= ClayTheme::e(ClayTheme::assetUrl('assets/images/logo.svg')) ?&gt;" alt=""&gt;</pre></section>
      <section class="card wiki-section" id="theme-override"><h2>视图覆盖规则</h2><p>默认视图：</p><pre class="codebox">app/views/web/home/index.php</pre><p>主题覆盖：</p><pre class="codebox">themes/clay_light/views/web/home/index.php</pre><p>只覆盖你需要改动的页面即可，不需要复制全部默认视图。这样更容易跟随官方升级。</p><ul><li>主题只覆盖通过 <code>theme_view()</code> 渲染的前台视图；后台管理视图不属于主题覆盖范围。</li><li>控制器已大量使用 <code>theme_view('web/...')</code>，因此主题目录里的路径必须从 <code>views/web/</code> 开始保持一致。</li><li>布局文件也可以覆盖，例如 <code>themes/clay_light/views/web/layouts/main.php</code>，但必须保留必要的 CSRF、Hook、静态资源和底部脚本输出。</li></ul></section>
      <section class="card wiki-section" id="theme-assets"><h2>资源组织</h2><pre class="codebox">assets/
├── css/theme.css
├── js/theme.js
└── images/logo.svg</pre><ul><li>CSS/JS 文件命名要清晰，不要覆盖全局变量太多。</li><li>默认布局会通过 <code>theme_assets()</code> 自动输出当前主题的 <code>assets/css/theme.css</code>；如果覆盖布局，请保留等价的主题资源加载逻辑。</li><li>主题内引用资源建议使用 <code>ThemeApi::assetUrl()</code>，不要写死域名。</li><li>图片尽量压缩，避免主题包体积过大。</li><li>如果引入第三方库，要说明来源和许可协议。</li></ul></section>
      <section class="card wiki-section" id="theme-style"><h2>样式与交互规范</h2><ul><li>移动端优先，避免大块固定宽度布局。</li><li>兼容暗色模式，优先使用系统 CSS 变量。</li><li>按钮、表单、列表保持简洁，不要过度装饰。</li><li>不要移除必要的 CSRF 字段、隐藏字段和提交按钮 name/value。</li><li>图片、头像、帖子卡片需要考虑长内容和空状态。</li></ul></section>
      <section class="card wiki-section" id="theme-compat"><h2>兼容与表单规则</h2><ul><li>不要删除表单中的 <code>csrf_field()</code>。</li><li>不要修改后端依赖的 <code>name</code> 字段，例如标题、内容、板块 ID。</li><li>保留登录态、未登录态、空状态、错误提示。</li><li>列表页要测试长标题、无头像、无图片、多图、暗色模式。</li><li>主题只覆盖展示层，不应直接写数据库。</li></ul></section>
      <section class="card wiki-section" id="theme-license"><h2>授权与付费主题</h2><p>付费主题同样使用市场授权注入。主题包声明 <code>license.required=true</code> 后，论坛在安装、启用和渲染时会校验授权；授权无效时自动回退默认主题。</p><pre class="codebox">{
  "type": "theme",
  "slug": "premium_dark",
  "name": "Premium Dark",
  "version": "1.0.0",
  "license": {
    "required": true,
    "protected_features": ["theme_activate", "theme_render"]
  }
}</pre><ul><li>官方站只会向已购买并绑定域名的站点注入有效授权。</li><li>论坛端启用付费主题前会校验授权。</li><li>授权失效或域名不匹配时，不会继续渲染该主题。</li></ul></section>
      <section class="card wiki-section" id="theme-package"><h2>主题打包发布</h2><ol><li>复制示例主题工程并修改 slug/name/version/api_version。</li><li>按需覆盖页面视图，不要一次性复制全部默认视图。</li><li>补充主题 CSS/JS，并确认覆盖布局时仍加载主题资源、CSRF、Hook 和必要脚本。</li><li>测试登录/未登录、移动端、暗色模式、空状态、长内容、主题切换和授权失效回退。</li><li>确认没有运行数据、日志、上传文件、缓存、私钥、数据库配置、<code>license.json</code>、<code>market-license.json</code>。</li><li>压缩主题根目录；市场安装器支持 <code>market.json</code> 在 zip 根目录或 <code>slug/market.json</code>，但解压后主题根目录必须包含 <code>theme.json</code>。</li><li>提交官方审核。</li></ol><div class="wiki-note">公益开发者只能发布免费主题；普通开发者可以发布免费或付费主题。付费主题授权文件由官方站按购买和绑定域名动态注入，开发者不要把授权文件打进分发包。</div></section>
      <section class="card wiki-section" id="theme-example"><h2>主题示例工程</h2><div class="wiki-download"><div><strong>Clay Light 示例主题</strong><p class="muted">包含 market.json、theme.json、api_version 声明、assets/css/theme.css、views/example-snippet.php 和 README。</p></div><?php if($canDownloadExamples): ?><a class="btn" href="/index.php?path=devdocs/download&id=theme-clay-light">下载主题工程</a><?php else: ?><a class="btn btn-light" href="/index.php?path=developer">成为开发者后下载</a><?php endif; ?></div></section>
    </article>
    <?php endif; ?>
  </main>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
