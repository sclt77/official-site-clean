<?php $pageTitle='完整包预览'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<div class="card">
  <h2>完整包预览</h2>
  <div style="color:var(--text-soft);margin-top:6px;">版本：<?= htmlspecialchars($pkg['version']) ?> / 文件：<?= htmlspecialchars($pkg['filename']) ?></div>
</div>

<div class="card">
  <h3>目录树</h3>
  <div class="table-wrap">
  <table class="table">
    <thead><tr><th>文件路径</th><th>替换</th></tr></thead>
    <tbody>
    <?php foreach ($tree as $path): ?>
      <tr>
        <td style="font-family:Consolas,monospace;"><?= htmlspecialchars($path) ?></td>
        <td>
          <form method="post" action="/admin.php?path=fullpacks/replace" enctype="multipart/form-data" style="display:flex;gap:8px;align-items:center;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int)$pkg['id'] ?>">
            <input type="hidden" name="path" value="<?= htmlspecialchars($path) ?>">
            <input class="input" type="file" name="file" required style="max-width:260px;">
            <button class="btn btn-light" type="submit">替换并重打包</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<style>
.table td form{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.table td .input[type="file"]{max-width:260px;}
</style>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
