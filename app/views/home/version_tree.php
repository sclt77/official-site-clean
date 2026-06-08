<?php
$pageTitle = ($productLabel ?? 'ClayBBS') . ' 版本节点图 - ' . ($site['site_name'] ?? 'Clay官方站');
require dirname(__DIR__) . '/layouts/main.php';
$treeJson = json_encode($treeData ?? ['nodes'=>[], 'edges'=>[]], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>
<div class="card version-node-board">
  <div class="board-head">
    <div>
      <span class="board-kicker">Release Graph</span>
      <h1><?= htmlspecialchars($productLabel ?? 'ClayBBS') ?> 版本节点图</h1>
      <p class="muted">公开展示 <?= htmlspecialchars($productLabel ?? 'ClayBBS') ?> 完整包和热更新的版本关系。拖动空白处可以移动视图，放大后能看得更清楚；页面会自动跳到最新版本。</p>
      <div class="product-tabs version-product-tabs" aria-label="产品版本树切换">
        <a class="<?= ($product ?? 'claybbs') === 'claybbs' ? 'active' : '' ?>" href="/index.php?path=history&product=claybbs">ClayBBS 版本树</a>
        <a class="<?= ($product ?? 'claybbs') === 'cutot' ? 'active' : '' ?>" href="/index.php?path=history&product=cutot">CUTOT 版本树</a>
      </div>
      <div class="board-links">
        <a href="/index.php?path=history/full&product=<?= urlencode($product ?? 'claybbs') ?>">完整包列表</a>
        <a href="/index.php?path=history/diff&product=<?= urlencode($product ?? 'claybbs') ?>">热更新列表</a>
      </div>
    </div>
    <div class="board-tools">
      <button class="btn btn-light" type="button" data-zoom="out">缩小</button>
      <button class="btn btn-light" type="button" data-focus-latest>最新版本</button>
      <button class="btn btn-light" type="button" data-zoom="in">放大</button>
    </div>
  </div>
  <div class="graph-shell" id="versionGraphShell">
    <div class="graph-world" id="versionGraphWorld">
      <svg class="graph-lines" id="versionGraphLines" xmlns="http://www.w3.org/2000/svg"></svg>
      <div class="graph-nodes" id="versionGraphNodes"></div>
    </div>
  </div>
</div>
<div class="version-modal" id="versionNodeModal" hidden>
  <div class="version-modal-mask" data-modal-close></div>
  <div class="version-modal-card" role="dialog" aria-modal="true" aria-label="版本详情">
    <button class="version-modal-close" type="button" data-modal-close>×</button>
    <div id="versionNodeDetail"></div>
  </div>
</div>
<style>
.version-node-board{padding:0;overflow:hidden;border-color:#bfdbfe}.board-head{display:flex;justify-content:space-between;gap:18px;align-items:flex-start;padding:26px 26px 18px;border-bottom:1px solid var(--line);background:radial-gradient(circle at top right,#dbeafe 0,#fff 44%,#ecfeff 100%)}.board-kicker{display:inline-flex;border-radius:999px;background:#eff6ff;color:#2563eb;padding:5px 9px;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase}.board-head h1{margin:10px 0 8px;font-size:38px;line-height:1.12;letter-spacing:-.04em}.board-tools{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.graph-shell{height:min(680px,72vh);min-height:460px;position:relative;overflow:hidden;background:radial-gradient(circle at 20% 12%,rgba(37,99,235,.10),transparent 28%),linear-gradient(90deg,rgba(148,163,184,.13) 1px,transparent 1px),linear-gradient(rgba(148,163,184,.13) 1px,transparent 1px),#f8fafc;background-size:auto,36px 36px,36px 36px,auto;cursor:grab;touch-action:none}.graph-shell:active{cursor:grabbing}.graph-world{position:absolute;left:0;top:0;width:1400px;height:860px;transform-origin:0 0}.graph-lines{position:absolute;inset:0;width:100%;height:100%;overflow:visible}.graph-nodes{position:absolute;inset:0}.v-node{position:absolute;width:230px;min-height:126px;transform:translate(-50%,-50%);border:1px solid #dbeafe;border-radius:18px;background:rgba(255,255,255,.92);box-shadow:0 18px 50px rgba(15,23,42,.10);padding:14px;text-align:left;cursor:pointer;transition:.16s ease;backdrop-filter:blur(8px)}.v-node:hover,.v-node.active{transform:translate(-50%,-50%) translateY(-2px);border-color:#60a5fa;box-shadow:0 22px 60px rgba(37,99,235,.18)}.v-node.latest{border-color:#22c55e;box-shadow:0 22px 60px rgba(34,197,94,.18)}.v-node.diff{border-color:#bae6fd}.v-node .row{display:flex;justify-content:space-between;gap:10px;align-items:flex-start}.v-node .ver{font-size:22px;font-weight:950;color:#0f172a;line-height:1}.v-node .type{border-radius:999px;padding:4px 8px;font-size:11px;font-weight:900;background:#dcfce7;color:#15803d;white-space:nowrap}.v-node.diff .type{background:#e0f2fe;color:#0369a1}.v-node.force .type{background:#ffedd5;color:#c2410c}.v-node .meta{margin-top:10px;color:#64748b;font-size:12px;line-height:1.55}.v-node .notes{margin-top:10px;color:#334155;font-size:12px;line-height:1.55;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.v-node .chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.v-node .chip{font-size:11px;font-weight:800;color:#475569;background:#f1f5f9;border-radius:999px;padding:4px 7px}.edge-path{fill:none;stroke:#93c5fd;stroke-width:3;stroke-linecap:round;stroke-linejoin:round}.edge-arrow{fill:#60a5fa}.edge-label{font-size:11px;fill:#64748b;font-weight:800}.version-modal{position:fixed;inset:0;z-index:80;display:flex;align-items:center;justify-content:center;padding:18px}.version-modal[hidden]{display:none}.version-modal-mask{position:absolute;inset:0;background:rgba(15,23,42,.42);backdrop-filter:blur(4px)}.version-modal-card{position:relative;width:min(520px,100%);max-height:min(620px,82vh);overflow:auto;background:#fff;border:1px solid #dbeafe;border-radius:22px;box-shadow:0 30px 100px rgba(15,23,42,.28);padding:20px;color:#334155;font-size:13px;line-height:1.7}.version-modal-close{position:absolute;right:12px;top:10px;border:0;background:#f1f5f9;color:#64748b;width:30px;height:30px;border-radius:999px;font-size:20px;line-height:1;cursor:pointer}.version-modal-close:hover{background:#e2e8f0;color:#0f172a}#versionNodeDetail strong{font-size:19px;color:#0f172a}#versionNodeDetail .btn{margin-top:8px}#versionNodeDetail .notes-full{white-space:pre-wrap;margin-top:10px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:12px}.latest-pulse{position:absolute;width:260px;height:156px;border-radius:24px;border:2px solid rgba(34,197,94,.28);transform:translate(-50%,-50%);pointer-events:none;animation:pulse 1.8s ease infinite}@keyframes pulse{0%{opacity:.8;scale:.96}100%{opacity:0;scale:1.18}}@media(max-width:768px){.board-head{display:block;padding:20px}.board-head h1{font-size:30px}.board-tools{margin-top:12px;justify-content:flex-start}.graph-shell{height:68vh;min-height:440px}.v-node{width:210px}.v-node .ver{font-size:20px}}
.version-product-tabs{display:flex;gap:10px;flex-wrap:wrap;margin:14px 0 12px}.version-product-tabs a{display:inline-flex;align-items:center;justify-content:center;min-width:170px;padding:13px 18px;border:1px solid #dbeafe;border-radius:16px;background:rgba(255,255,255,.78);color:#475569;text-decoration:none;font-weight:950;box-shadow:0 8px 24px rgba(15,23,42,.06)}.version-product-tabs a.active{border-color:#2563eb;background:linear-gradient(135deg,#2563eb,#4f46e5);color:#fff;box-shadow:0 16px 34px rgba(37,99,235,.22)}@media(max-width:640px){.version-product-tabs{display:grid;grid-template-columns:1fr 1fr}.version-product-tabs a{min-width:0;padding:11px 10px;font-size:13px}}</style>
<script>
(function(){
const data = <?= $treeJson ?: '{"nodes":[],"edges":[]}' ?>;
const shell=document.getElementById('versionGraphShell'), world=document.getElementById('versionGraphWorld'), svg=document.getElementById('versionGraphLines'), box=document.getElementById('versionGraphNodes'), detail=document.getElementById('versionNodeDetail'), modal=document.getElementById('versionNodeModal');
const nodes=(data.nodes||[]).slice().sort((a,b)=>String(a.version).localeCompare(String(b.version),undefined,{numeric:true}));
const edges=data.edges||[];const latest=nodes[nodes.length-1]||null;const pos=new Map();
const levels=new Map();nodes.forEach((n,i)=>levels.set(n.id,i));
const w=Math.max(1100,nodes.length*260+240),h=760;world.style.width=w+'px';world.style.height=h+'px';svg.setAttribute('viewBox',`0 0 ${w} ${h}`);
nodes.forEach((n,i)=>{const x=160+i*250;const y=h/2 + (i%2===0?-95:95);pos.set(n.id,{x,y});});
svg.innerHTML='<defs><marker id="arrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto"><path class="edge-arrow" d="M0,0 L0,6 L9,3 z"/></marker></defs>';
function drawEdge(a,b){const p1=pos.get(a),p2=pos.get(b);if(!p1||!p2)return;const mid=(p1.x+p2.x)/2;const d=`M ${p1.x+115} ${p1.y} C ${mid} ${p1.y}, ${mid} ${p2.y}, ${p2.x-115} ${p2.y}`;const path=document.createElementNS('http://www.w3.org/2000/svg','path');path.setAttribute('d',d);path.setAttribute('class','edge-path');path.setAttribute('marker-end','url(#arrow)');svg.appendChild(path);} 
edges.length?edges.forEach(e=>drawEdge(e.from,e.to)):nodes.forEach((n,i)=>{if(i>0)drawEdge(nodes[i-1].id,n.id)});
function esc(s){return String(s).replace(/[&<>\"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]));}
function nodeType(n){return n.hasFull?'完整包':'热更新'}
nodes.forEach(n=>{const p=pos.get(n.id);const el=document.createElement('button');el.type='button';el.className='v-node '+(!n.hasFull?'diff ':'')+(n.force||n.level==='critical'?'force ':'')+(latest&&latest.id===n.id?'latest':'');el.style.left=p.x+'px';el.style.top=p.y+'px';el.dataset.id=n.id;el.innerHTML=`<div class="row"><div class="ver">v${esc(n.version)}</div><span class="type">${nodeType(n)}</span></div><div class="meta">${esc(n.createdAt||'-')}<br>等级：${esc(n.level||'normal')}${n.force?' / 强制':''}</div><div class="notes">${esc(n.notes||'暂无说明')}</div><div class="chips">${n.hasFull?(n.isLatestFull?'<span class="chip">最新版可下载</span>':'<span class="chip">历史包仅展示</span>'):''}${n.hasDiff?'<span class="chip">后台热更新</span>':''}</div>`;el.onclick=()=>selectNode(n,el);box.appendChild(el);if(latest&&latest.id===n.id){const pulse=document.createElement('div');pulse.className='latest-pulse';pulse.style.left=p.x+'px';pulse.style.top=p.y+'px';box.appendChild(pulse);}});
let activeEl=null;function selectNode(n,el,openModal=true){if(activeEl)activeEl.classList.remove('active');activeEl=el;activeEl.classList.add('active');if(openModal)modal.hidden=false;detail.innerHTML=`<strong>v${esc(n.version)}</strong><br>类型：${nodeType(n)}<br>时间：${esc(n.createdAt||'-')}<br>等级：${esc(n.level||'normal')}${n.force?' / 强制更新':''}<br>${n.hasFull&&n.fullId&&n.isLatestFull?`<a class="btn" href="/index.php?path=download/full&id=${n.fullId}">下载最新版完整包</a>`:(n.hasFull?'<span class="muted">历史完整包不可下载，请下载最新版完整包。</span>':'<span class="muted">更新包仅供论坛后台热更新，不提供前台下载。</span>')}<div class="notes-full">${esc(n.notes||'暂无说明')}</div>`;}
let scale=.9,tx=0,ty=0,drag=false,isPinching=false,sx=0,sy=0,ox=0,oy=0,lastPinch=0;function apply(){world.style.transform=`translate3d(${tx}px,${ty}px,0) scale(${scale})`;}
function focusNode(n,animate=true){const p=pos.get(n.id);if(!p)return;scale=Math.min(1.15,Math.max(.78,shell.clientWidth<768?.85:.95));tx=shell.clientWidth/2-p.x*scale;ty=shell.clientHeight/2-p.y*scale;apply();const el=box.querySelector(`[data-id="${CSS.escape(n.id)}"]`);if(el)selectNode(n,el,false);} 
shell.addEventListener('pointerdown',e=>{if(isPinching)return;drag=true;shell.setPointerCapture(e.pointerId);sx=e.clientX;sy=e.clientY;ox=tx;oy=ty;});shell.addEventListener('pointermove',e=>{if(!drag||isPinching)return;tx=ox+e.clientX-sx;ty=oy+e.clientY-sy;apply();});shell.addEventListener('pointerup',()=>drag=false);shell.addEventListener('pointercancel',()=>drag=false);
shell.addEventListener('wheel',e=>{e.preventDefault();zoomAt(e.clientX,e.clientY,e.deltaY<0?1.09:.91);},{passive:false});
shell.addEventListener('touchmove',e=>{if(e.touches.length===2){e.preventDefault();isPinching=true;drag=false;const a=e.touches[0],b=e.touches[1];const d=Math.hypot(a.clientX-b.clientX,a.clientY-b.clientY);const r=shell.getBoundingClientRect();const cx=r.left+r.width/2,cy=r.top+r.height/2;if(lastPinch>0){const ratio=d/lastPinch;if(ratio>.96&&ratio<1.04)return;zoomAt(cx,cy,ratio);}lastPinch=d;}},{passive:false});shell.addEventListener('touchend',e=>{if(e.touches.length<2){lastPinch=0;setTimeout(()=>{isPinching=false;},80);}});
function zoomAt(cx,cy,f){const old=scale;scale=Math.max(.35,Math.min(1.8,scale*f));const r=shell.getBoundingClientRect();const mx=cx-r.left,my=cy-r.top;tx=mx-(mx-tx)*(scale/old);ty=my-(my-ty)*(scale/old);apply();}
document.querySelector('[data-zoom="in"]').onclick=()=>zoomAt(shell.clientWidth/2,shell.clientHeight/2,1.14);document.querySelector('[data-zoom="out"]').onclick=()=>zoomAt(shell.clientWidth/2,shell.clientHeight/2,.88);document.querySelector('[data-focus-latest]').onclick=()=>latest&&focusNode(latest);document.querySelectorAll('[data-modal-close]').forEach(el=>el.addEventListener('click',()=>{modal.hidden=true;}));document.addEventListener('keydown',e=>{if(e.key==='Escape')modal.hidden=true;});
requestAnimationFrame(()=>{if(latest)focusNode(latest,false);else apply();});
})();
</script>
<?php require dirname(__DIR__) . '/layouts/footer.php'; ?>
