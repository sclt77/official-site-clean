<?php $pageTitle='编辑资料'; require dirname(__DIR__) . '/layouts/main.php'; ?>
<div class=\"page-shell\">
<section class="card account-hero">
  <div><span class="badge">Profile Settings</span><h1>编辑个人资料</h1><p>点击头像或背景区域选择图片，保存后会显示在用户中心和公开主页。</p></div>
  <div class="account-hero-stat"><span>资料展示</span><strong>头像 / 背景</strong><small>同步用户中心与公开主页</small></div>
</section>
<?php if (!empty($error)): ?><div class="card" style="background:#fee2e2;color:#b91c1c;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data" action="/index.php?path=me/edit-profile" class="card profile-edit-form">
  <?= csrf_field() ?>
  <label class="cover-upload" id="coverPreview" style="<?= !empty($user['cover']) ? 'background-image:url(' . htmlspecialchars($user['cover']) . ');' : '' ?>"><input type="file" name="cover" accept="image/*" data-preview="cover"><span>点击背景上传背景图</span></label>
  <label class="avatar-upload" id="avatarPreview"><?php if (!empty($user['avatar'])): ?><img src="<?= htmlspecialchars($user['avatar']) ?>" alt=""><?php else: ?><span><?= htmlspecialchars(strtoupper(substr((string)($user['name'] ?: 'U'),0,1))) ?></span><?php endif; ?><input type="file" name="avatar" accept="image/*" data-preview="avatar"><em>点击头像上传头像</em></label>
  <label>昵称<input class="input" name="name" value="<?= htmlspecialchars((string)($user['name'] ?? '')) ?>"></label>
  <label>个人介绍<textarea class="input" name="bio" rows="4"><?= htmlspecialchars((string)($user['bio'] ?? '')) ?></textarea></label>
  <button class="btn" type="submit">保存资料</button>
</form>
<style>.account-hero{display:grid;grid-template-columns:minmax(0,1fr) 280px;gap:20px;align-items:stretch;padding:28px;background:radial-gradient(circle at top right,#dbeafe 0,#fff 42%,#ecfeff 100%);border-color:#bfdbfe}.account-hero h1{margin:14px 0 10px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.account-hero p{color:#64748b;line-height:1.8}.account-hero-stat{display:grid;align-content:center;gap:8px;border:1px solid #dbeafe;border-radius:20px;background:rgba(255,255,255,.78);box-shadow:0 18px 50px rgba(37,99,235,.10);padding:18px}.account-hero-stat span,.account-hero-stat small{color:#64748b;font-size:13px;font-weight:800}.account-hero-stat strong{font-size:24px;letter-spacing:-.03em}.profile-edit-form{display:grid;gap:16px}.cover-upload{height:190px;border-radius:20px;background:linear-gradient(135deg,#eff6ff,#ecfeff);background-size:cover;background-position:center;display:grid;place-items:center;color:#2563eb;font-weight:900;cursor:pointer;border:1px dashed #93c5fd;overflow:hidden}.cover-upload input,.avatar-upload input{display:none}.cover-upload span{background:rgba(255,255,255,.84);border-radius:999px;padding:9px 14px;box-shadow:0 12px 32px rgba(15,23,42,.08)}.avatar-upload{width:120px;display:grid;gap:8px;justify-items:center;cursor:pointer}.avatar-upload img,.avatar-upload>span{width:92px;height:92px;border-radius:28px;background:linear-gradient(135deg,#2563eb,#06b6d4);display:grid;place-items:center;color:#fff;font-size:34px;font-weight:900;object-fit:cover;box-shadow:0 18px 50px rgba(37,99,235,.18)}.avatar-upload em{font-style:normal;color:#64748b;font-size:12px}@media(max-width:720px){.account-hero{grid-template-columns:1fr;padding:20px}.account-hero h1{font-size:30px}.cover-upload{height:160px}}</style>

<script>
(function(){
  function previewFile(input){
    var file=input.files&&input.files[0]; if(!file) return;
    var url=URL.createObjectURL(file);
    if(input.dataset.preview==='cover'){
      var box=document.getElementById('coverPreview');
      if(box){box.style.backgroundImage='url('+url+')'; box.classList.add('has-preview');}
    }
    if(input.dataset.preview==='avatar'){
      var box=document.getElementById('avatarPreview');
      if(box){
        var old=box.querySelector('img, span'); if(old) old.remove();
        var img=document.createElement('img'); img.src=url; img.alt=''; box.insertBefore(img, box.firstChild);
      }
    }
  }
  document.querySelectorAll('input[data-preview]').forEach(function(input){
    input.addEventListener('change', function(){ previewFile(input); });
  });
})();
</script>
</div>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
