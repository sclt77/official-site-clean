</main></div>
</div>
<?php
$siteCfg = $siteCfg ?? ['footer_text' => '© ' . date('Y') . ' Clay官方站'];
?>
<footer class="footer"><span><?= htmlspecialchars($siteCfg['footer_text'] ?? ('© ' . date('Y') . ' Clay官方站')) ?></span></footer>
<style>
.ajax-toast-wrap{position:fixed;right:18px;top:82px;z-index:9999;display:grid;gap:10px;max-width:min(380px,calc(100vw - 32px));}.ajax-toast{background:#0f172a;color:#fff;border-radius:16px;padding:13px 15px;box-shadow:0 18px 45px rgba(15,23,42,.22);font-size:13px;line-height:1.5;animation:ajaxToastIn .18s ease both}.ajax-toast.ok{background:#166534}.ajax-toast.err{background:#991b1b}.ajax-toast.info{background:#0f172a}.ajax-busy{opacity:.62;pointer-events:none}@keyframes ajaxToastIn{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
</style>
<script>
(function(){
  if(window.__clayAjaxForms)return;window.__clayAjaxForms=true;
  function toast(msg,type){var wrap=document.querySelector('.ajax-toast-wrap');if(!wrap){wrap=document.createElement('div');wrap.className='ajax-toast-wrap';document.body.appendChild(wrap);}var el=document.createElement('div');el.className='ajax-toast '+(type||'info');el.textContent=msg||'已完成';wrap.appendChild(el);setTimeout(function(){el.style.opacity='0';el.style.transform='translateY(-6px)';},2600);setTimeout(function(){el.remove();},3100);}
  function shouldSkip(form){if(!form||form.method.toLowerCase()!=='post')return true;if(form.hasAttribute('data-no-ajax'))return true;if(form.enctype&&form.enctype.toLowerCase()==='multipart/form-data'&&form.querySelector('input[type=file]'))return true;var action=form.getAttribute('action')||location.href;if(action.indexOf('install.php')!==-1||action.indexOf('logout')!==-1)return true;return false;}
  function successText(form){var act=(form.querySelector('input[name="_action"]')||{}).value||'';var submitUrl=form.getAttribute('action')||'';if(act.indexOf('delete')!==-1||/delete/i.test(submitUrl))return '删除成功';if(act.indexOf('create')!==-1)return '添加成功';if(act.indexOf('update')!==-1||/toggle|settings|users|sections|announcements|banners|roles/.test(submitUrl))return '保存成功';return '操作成功';}
  document.addEventListener('submit',function(e){var form=e.target;if(shouldSkip(form))return;var inlineSubmit=form.getAttribute('onsubmit');if(inlineSubmit){try{var ok=(new Function('event',inlineSubmit)).call(form,e);if(ok===false){e.preventDefault();e.stopImmediatePropagation();return;}}catch(err){e.preventDefault();e.stopImmediatePropagation();toast('操作已取消：'+err.message,'err');return;}}
    e.preventDefault();var btn=e.submitter||form.querySelector('button[type=submit],button:not([type])');var old=btn?btn.textContent:'';if(btn){btn.disabled=true;btn.textContent='处理中...';}form.classList.add('ajax-busy');var submitUrl=form.getAttribute('action')||location.href;fetch(submitUrl,{method:'POST',body:(function(){var fd=new FormData(form);if(btn&&btn.name)fd.set(btn.name,btn.value);return fd;})(),credentials:'same-origin',headers:{'X-Requested-With':'XMLHttpRequest','Accept':'text/html,application/json'}}).then(function(res){if(!res.ok)throw new Error('HTTP '+res.status);return res.text();}).then(function(text){var data=null,redirect='';try{data=JSON.parse(text);}catch(e){}if(data&&data.ok===false)throw new Error(data.message||data.error||'操作失败');if(data&&data.redirect)redirect=String(data.redirect);toast((data&&(data.message||data.msg))||successText(form),'ok');setTimeout(function(){if(redirect&&redirect!==location.href)location.href=redirect;else location.reload();},650);}).catch(function(err){toast('操作失败：'+err.message,'err');}).finally(function(){form.classList.remove('ajax-busy');if(btn){btn.disabled=false;btn.textContent=old;}});
  },true);
})();
</script>
</body>
</html>
