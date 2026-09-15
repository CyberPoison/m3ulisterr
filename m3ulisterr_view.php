<?php
// M3uListerr front-end - included by dashboard.php only. Renders the setup
// form, the login form, or the full dashboard. All dynamic values are escaped;
// the only inline script carries the per-response CSP nonce.

if (!defined('M3ULISTERR_APP')) {
    http_response_code(403);
    exit;
}

$nonce = $M3U_NONCE;
$csrf = m3uCsrf();
$loggedIn = m3uIsLoggedIn();

function h($s): string { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }

$css = <<<CSS
:root{
  --bg:#0a0e1a;--bg2:#0f1526;--card:#141b30;--card2:#1a2440;--line:#243050;
  --txt:#e8edf7;--dim:#8a97b8;--accent:#5b8dff;--accent2:#a06bff;--good:#37d39b;
  --warn:#ffb84d;--bad:#ff5c7a;--chip:#22304f;
}
*{box-sizing:border-box;margin:0;padding:0}
body{background:linear-gradient(160deg,#070a14,#0d1425 60%,#0a0f1f);color:var(--txt);
  font:14px/1.5 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;
  min-height:100vh}
a{color:var(--accent);text-decoration:none}
.wrap{display:flex;min-height:100vh}
.brand{display:flex;align-items:center;gap:10px;font-weight:800;font-size:20px;letter-spacing:-.5px}
.brand .dot{width:30px;height:30px;border-radius:9px;background:linear-gradient(135deg,var(--accent),var(--accent2));
  display:grid;place-items:center;font-size:16px;box-shadow:0 4px 18px rgba(91,141,255,.5)}
.brand b{background:linear-gradient(90deg,#8fb2ff,#c6a3ff);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
/* auth */
.auth{max-width:400px;margin:9vh auto;padding:0 18px}
.auth .box{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:30px;
  box-shadow:0 30px 80px rgba(0,0,0,.5)}
.auth h1{font-size:17px;margin:20px 0 6px}
.auth p{color:var(--dim);margin-bottom:20px;font-size:13px}
label{display:block;color:var(--dim);font-size:12px;margin:14px 0 6px;text-transform:uppercase;letter-spacing:.5px}
input,select,textarea{width:100%;background:var(--bg2);border:1px solid var(--line);border-radius:10px;
  padding:11px 13px;color:var(--txt);font-size:14px;font-family:inherit}
input:focus,textarea:focus,select:focus{outline:none;border-color:var(--accent);box-shadow:0 0 0 3px rgba(91,141,255,.15)}
.btn{background:linear-gradient(135deg,var(--accent),var(--accent2));border:none;color:#fff;font-weight:600;
  padding:12px 18px;border-radius:10px;cursor:pointer;font-size:14px;width:100%;margin-top:20px}
.btn:hover{filter:brightness(1.08)}
.btn.sm{width:auto;padding:8px 14px;margin:0;font-size:13px}
.btn.ghost{background:transparent;border:1px solid var(--line);color:var(--txt)}
.err{background:rgba(255,92,122,.12);border:1px solid rgba(255,92,122,.4);color:#ffb3c2;
  padding:10px 13px;border-radius:10px;font-size:13px;margin-top:14px}
.ok{background:rgba(55,211,155,.12);border:1px solid rgba(55,211,155,.4);color:#9ff0cf;
  padding:10px 13px;border-radius:10px;font-size:13px;margin-top:14px}
.hint{color:var(--dim);font-size:12px;margin-top:8px}
/* app */
.side{width:230px;background:rgba(10,14,26,.7);border-right:1px solid var(--line);padding:22px 16px;
  position:sticky;top:0;height:100vh;display:flex;flex-direction:column;gap:6px;flex-shrink:0}
.side .brand{margin-bottom:22px;padding:0 6px}
.nav{display:flex;flex-direction:column;gap:3px}
.nav a{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:10px;color:var(--dim);font-weight:500}
.nav a:hover{background:var(--card)}
.nav a.on{background:linear-gradient(135deg,rgba(91,141,255,.22),rgba(160,107,255,.14));color:#fff}
.nav a .ic{width:18px;text-align:center}
.side .foot{margin-top:auto;font-size:12px;color:var(--dim)}
.main{flex:1;padding:26px 30px;overflow-x:hidden;max-width:calc(100vw - 230px)}
.top{display:flex;align-items:center;justify-content:space-between;margin-bottom:22px;gap:12px;flex-wrap:wrap}
.top h2{font-size:22px;font-weight:700}
.muted{color:var(--dim);font-size:13px}
.cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:22px}
.stat{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:16px 18px}
.stat .n{font-size:26px;font-weight:800;letter-spacing:-1px}
.stat .l{color:var(--dim);font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin-top:3px}
.grid2{display:grid;grid-template-columns:1.3fr 1fr;gap:18px;margin-bottom:22px}
@media(max-width:1050px){.grid2{grid-template-columns:1fr}.main{max-width:100vw}}
.panel{background:var(--card);border:1px solid var(--line);border-radius:16px;padding:18px}
.panel h3{font-size:14px;margin-bottom:14px;display:flex;align-items:center;justify-content:space-between}
.panel h3 span{color:var(--dim);font-weight:500;font-size:12px}
.bar{display:flex;align-items:center;gap:10px;margin:7px 0;font-size:13px}
.bar .lb{width:110px;flex-shrink:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--dim)}
.bar .tr{flex:1;background:var(--bg2);border-radius:6px;height:16px;overflow:hidden}
.bar .fl{height:100%;background:linear-gradient(90deg,var(--accent),var(--accent2));border-radius:6px}
.bar .vv{width:38px;text-align:right;font-variant-numeric:tabular-nums;color:var(--txt)}
#globe{display:block;margin:0 auto;cursor:grab}
#globe:active{cursor:grabbing}
.globe-wrap{position:relative;min-height:340px;display:grid;place-items:center}
.tabtable{width:100%;border-collapse:collapse;font-size:13px}
.tabtable th{text-align:left;color:var(--dim);font-weight:600;font-size:11px;text-transform:uppercase;
  letter-spacing:.5px;padding:10px 10px;border-bottom:1px solid var(--line);position:sticky;top:0;background:var(--card)}
.tabtable td{padding:10px 10px;border-bottom:1px solid rgba(36,48,80,.5);vertical-align:middle}
.tabtable tr:hover td{background:var(--card2)}
.poster{width:40px;height:60px;border-radius:6px;object-fit:cover;background:var(--bg2);flex-shrink:0}
.movie-cell{display:flex;gap:10px;align-items:center}
.movie-cell .mt{font-weight:600}.movie-cell .my{color:var(--dim);font-size:12px}
.chip{display:inline-block;background:var(--chip);border:1px solid var(--line);border-radius:20px;
  padding:2px 9px;font-size:11px;margin:1px 2px;white-space:nowrap}
.chip.ad{background:rgba(55,211,155,.16);border-color:rgba(55,211,155,.4);color:#9ff0cf}
.chip.pm{background:rgba(255,184,77,.16);border-color:rgba(255,184,77,.4);color:#ffd9a3}
.chip.tv{background:rgba(160,107,255,.18);border-color:rgba(160,107,255,.45);color:#d3befd}
.chip.mv{background:rgba(91,141,255,.18);border-color:rgba(91,141,255,.45);color:#bcd0ff}
.pctbar{width:80px;background:var(--bg2);border-radius:5px;height:8px;overflow:hidden;display:inline-block;vertical-align:middle;margin-right:6px}
.pctbar i{display:block;height:100%;background:linear-gradient(90deg,var(--good),var(--accent))}
.tablewrap{overflow-x:auto;max-height:70vh;overflow-y:auto}
.mono{font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:12px}
.grid-movies{display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:14px}
.mcard{background:var(--card);border:1px solid var(--line);border-radius:14px;overflow:hidden;position:relative}
.mcard img{width:100%;aspect-ratio:2/3;object-fit:cover;display:block;background:var(--bg2)}
.mcard .mi{padding:10px}
.mcard .mi .t{font-weight:600;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.mcard .pl{position:absolute;top:8px;right:8px;background:rgba(0,0,0,.7);border-radius:20px;padding:3px 9px;font-size:12px;font-weight:700}
.sec{display:none}.sec.on{display:block}
.rowflex{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.field{margin-bottom:14px}
.toggle{display:flex;align-items:center;gap:10px}
.toggle input{width:auto}
.overlay{position:fixed;inset:0;background:rgba(4,7,14,.7);display:none;place-items:center;z-index:50;padding:18px}
.overlay.on{display:grid}
.modal{background:var(--card);border:1px solid var(--line);border-radius:18px;padding:26px;max-width:440px;width:100%}
.modal h3{margin-bottom:6px}
.spin{color:var(--dim);padding:40px;text-align:center}
CSS;

// ---------------------------------------------------------------------------
// Not authenticated: setup (first run) or login
// ---------------------------------------------------------------------------
if (!$loggedIn):
?><!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>M3uListerr</title>
<style><?= $css ?></style>
</head><body>
<div class="auth"><div class="box">
  <div class="brand"><span class="dot">M</span><span>M3u<b>Listerr</b></span></div>
<?php if ($needsSetup): ?>
  <h1>Create the admin account</h1>
  <p>This is the first run. Set the username and password you'll use to sign in. Stored hashed (Argon2id) in a private database.</p>
  <?php if (!empty($setupErrors)): ?><div class="err"><?= h(implode(' ', $setupErrors)) ?></div><?php endif; ?>
  <form method="post" action="?action=setup" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <label>Username</label>
    <input name="username" required minlength="3" maxlength="32" autofocus>
    <label>Password</label>
    <input name="password" type="password" required minlength="10">
    <div class="hint">At least 10 characters, with letters and digits.</div>
    <button class="btn" type="submit">Create account &amp; sign in</button>
  </form>
<?php else: ?>
  <h1>Sign in</h1>
  <p>Enter your dashboard credentials.</p>
  <?php if (!empty($loginError)): ?><div class="err"><?= h($loginError) ?></div><?php endif; ?>
  <form method="post" action="?action=login" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
    <label>Username</label>
    <input name="username" required autofocus>
    <label>Password</label>
    <input name="password" type="password" required>
    <button class="btn" type="submit">Sign in</button>
  </form>
<?php endif; ?>
</div></div>
</body></html>
<?php
    exit;
endif;

// ---------------------------------------------------------------------------
// Authenticated dashboard
// ---------------------------------------------------------------------------
?><!doctype html><html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>M3uListerr</title>
<style><?= $css ?></style>
</head><body>
<div class="wrap">
  <aside class="side">
    <div class="brand"><span class="dot">M</span><span>M3u<b>Listerr</b></span></div>
    <nav class="nav">
      <a data-sec="overview" class="on"><span class="ic">◎</span> Overview</a>
      <a data-sec="globe"><span class="ic">◐</span> Globe</a>
      <a data-sec="sessions"><span class="ic">▤</span> Sessions</a>
      <a data-sec="movies"><span class="ic">▦</span> Titles</a>
      <a data-sec="prewarmed"><span class="ic">⚡</span> Prewarmed</a>
      <a data-sec="cache"><span class="ic">▣</span> Cache</a>
      <a data-sec="config"><span class="ic">⚙</span> Config</a>
      <a data-sec="account"><span class="ic">◈</span> Account</a>
    </nav>
    <div class="foot">
      Signed in as <b><?= h($_SESSION['uname']) ?></b><br>
      <form method="post" action="?action=logout" style="margin-top:8px">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>">
        <button class="btn ghost sm" type="submit">Sign out</button>
      </form>
    </div>
  </aside>
  <main class="main">
    <div class="top">
      <h2 id="secTitle">Overview</h2>
      <div class="rowflex">
        <span class="muted" id="lastImport"></span>
        <button class="btn sm" id="refreshBtn">↻ Refresh data</button>
      </div>
    </div>

    <section class="sec on" data-sec="overview">
      <div id="ovBanner"></div>
      <div class="cards" id="statCards"><div class="spin">Loading…</div></div>
      <div class="grid2">
        <div class="panel"><h3>Debrid service used <span>per session</span></h3><div id="chDebrid"></div></div>
        <div class="panel"><h3>Requests by country</h3><div id="chCountry"></div></div>
      </div>
      <div class="grid2">
        <div class="panel"><h3>Device / player</h3><div id="chDevice"></div></div>
        <div class="panel"><h3>Account &amp; language</h3><div id="chAccount"></div><div id="chLang" style="margin-top:12px"></div></div>
      </div>
      <div class="grid2">
        <div class="panel"><h3>Client ISP <span>who requested &amp; watched</span></h3><div id="chIsp"></div></div>
        <div class="panel"><h3>Content type <span>movies vs TV</span></h3><div id="chKind"></div></div>
      </div>
    </section>

    <section class="sec" data-sec="globe">
      <div class="panel">
        <h3>Where movies &amp; TV shows are requested from <span>drag to rotate</span></h3>
        <div class="globe-wrap"><svg id="globe" width="600" height="600" viewBox="0 0 600 600"></svg></div>
      </div>
    </section>

    <section class="sec" data-sec="sessions">
      <div class="panel">
        <h3>Playback sessions <span id="sessCount"></span></h3>
        <div class="tablewrap"><table class="tabtable" id="sessTable"></table></div>
      </div>
    </section>

    <section class="sec" data-sec="movies">
      <div class="panel"><h3>Most requested movies &amp; TV shows</h3><div class="grid-movies" id="movieGrid"></div></div>
    </section>

    <section class="sec" data-sec="prewarmed">
      <div id="cacheBanner"></div>
      <div class="panel"><h3>Prewarmed content <span id="prewarmCount"></span></h3>
        <p class="muted" style="margin-bottom:14px">Titles resolved ahead of time so the first viewer play is instant. Green = still fresh in the durable store; grey = expired (will re-warm on the next prewarm run).</p>
        <div class="grid-movies" id="prewarmGrid"></div>
      </div>
    </section>

    <section class="sec" data-sec="cache">
      <div id="cacheBanner2"></div>
      <div class="panel"><h3>Durable cache <span>SQLite — survives deploys</span></h3>
        <p class="muted" style="margin-bottom:14px">Every resolved stream is stored here (the source of truth). <code>cache.json</code> is only the temporary hot layer and is rebuilt from this after a deploy.</p>
        <div class="tablewrap"><table class="tabtable" id="cacheTable"></table></div>
      </div>
    </section>

    <section class="sec" data-sec="config">
      <div class="panel" style="max-width:560px">
        <h3>Edit config.php <span>allow-listed settings</span></h3>
        <div id="configForm"><div class="spin">Loading…</div></div>
        <div id="configMsg"></div>
        <button class="btn" id="saveConfig">Save changes</button>
        <div class="hint">A timestamped backup is written and the file is syntax-checked before replacing. Secrets and code are never shown here or editable from this screen.</div>
      </div>
    </section>

    <section class="sec" data-sec="account">
      <div class="panel" style="max-width:460px">
        <h3>Change username &amp; password</h3>
        <div class="field"><label>Current password</label><input type="password" id="curPass" autocomplete="current-password"></div>
        <div class="field"><label>New username (optional)</label><input id="newUser" value="<?= h($_SESSION['uname']) ?>" autocomplete="off"></div>
        <div class="field"><label>New password (optional)</label><input type="password" id="newPass" autocomplete="new-password"></div>
        <div id="credMsg"></div>
        <button class="btn" id="saveCreds">Update credentials</button>
      </div>
    </section>
  </main>
</div>

<script nonce="<?= h($nonce) ?>" src="?asset=d3"></script>
<script nonce="<?= h($nonce) ?>" src="?asset=topojson"></script>
<script nonce="<?= h($nonce) ?>">
const CSRF = <?= json_encode($csrf) ?>;
const $ = s => document.querySelector(s);
const esc = s => String(s==null?'':s).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
async function api(q){ const r = await fetch('?action=api&q='+encodeURIComponent(q)); return r.json(); }
async function post(action, data){
  const body = new URLSearchParams(); body.set('csrf', CSRF);
  for (const k in data) body.set(k, data[k]);
  const r = await fetch('?action='+action, {method:'POST', body}); return r.json();
}

// nav
document.querySelectorAll('.nav a').forEach(a=>a.addEventListener('click',()=>{
  const sec=a.dataset.sec;
  document.querySelectorAll('.nav a').forEach(x=>x.classList.toggle('on',x===a));
  document.querySelectorAll('.sec').forEach(s=>s.classList.toggle('on',s.dataset.sec===sec));
  $('#secTitle').textContent=a.textContent.trim();
  if(sec==='globe') drawGlobe();
  if(sec==='sessions') loadSessions();
  if(sec==='cache') loadCache();
  if(sec==='prewarmed') loadPrewarmed();
  if(sec==='config') loadConfig();
}));

function barChart(el, rows, cls){
  if(!rows||!rows.length){el.innerHTML='<div class="muted">No data yet.</div>';return;}
  const max=Math.max(...rows.map(r=>r.count));
  el.innerHTML=rows.map(r=>`<div class="bar"><div class="lb">${esc(r.label)}</div>
    <div class="tr"><div class="fl" style="width:${Math.max(4,r.count/max*100)}%"></div></div>
    <div class="vv">${r.count}</div></div>`).join('');
}

let LAST=null;
async function loadOverview(){
  const d=await api('overview'); if(!d.ok)return; LAST=d;
  const s=d.stats;
  const cards=[['Sessions',s.sessions],['Resolves',s.resolves],['Unique IPs',s.uniqueIps],
    ['Countries',s.countries],['Avg resolve',s.avgResolveMs!=null?s.avgResolveMs+' ms':'—'],
    ['Avg segment',s.avgDeliverMs!=null?s.avgDeliverMs+' ms':'—'],
    ['Durable cache',s.resolvedCache],['Prewarmed',s.prewarmedCount!=null?s.prewarmedCount:'—']];
  $('#statCards').innerHTML=cards.map(c=>`<div class="stat"><div class="n">${esc(c[1])}</div><div class="l">${esc(c[0])}</div></div>`).join('');
  // cache.json health banner on the overview when it's missing/empty.
  if(d.cacheJson && (!d.cacheJson.exists || d.cacheJson.count===0)) renderCacheBanner(d.cacheJson,'ovBanner');
  else { const ob=$('#ovBanner'); if(ob) ob.innerHTML=''; }
  barChart($('#chDebrid'),d.charts.debrid);
  barChart($('#chCountry'),d.charts.country);
  barChart($('#chDevice'),d.charts.device);
  barChart($('#chAccount'),d.charts.account);
  barChart($('#chLang'),d.charts.lang);
  barChart($('#chIsp'),d.charts.isp);
  barChart($('#chKind'),d.charts.kind);
  renderMovies(d.topMovies);
  if(d.lastImport) $('#lastImport').textContent='Updated '+new Date(d.lastImport*1000).toLocaleString();
}

function renderMovies(movies){
  const g=$('#movieGrid');
  if(!movies||!movies.length){g.innerHTML='<div class="muted">No titles yet.</div>';return;}
  g.innerHTML=movies.map(m=>`<div class="mcard">
    <div class="pl">${m.plays}▶</div>
    ${m.poster_url?`<img src="${esc(m.poster_url)}" alt="" loading="lazy">`:'<div style="aspect-ratio:2/3;background:var(--bg2)"></div>'}
    <div class="mi"><div class="t" title="${esc(m.title)}"><span class="chip ${m.is_series?'tv':'mv'}">${esc(m.kind_label||'Movie')}</span> ${esc(m.title)}</div>
    <div class="my">${esc(m.year||'')} ${m.debrid.map(x=>`<span class="chip ${x.toLowerCase()==='ad'?'ad':x.toLowerCase()==='pm'?'pm':''}">${esc(x)}</span>`).join('')}</div></div>
  </div>`).join('');
}

async function loadSessions(){
  const d=await api('sessions'); if(!d.ok)return;
  $('#sessCount').textContent=d.sessions.length+' rows';
  const head=`<thead><tr><th>Title</th><th>Type</th><th>Account</th><th>Lang</th><th>Release</th><th>Debrid</th>
    <th>Playback</th><th>Subs</th><th>Country</th><th>City / Zip</th><th>Client ISP</th><th>Device</th><th>User agent</th><th>IP</th><th>Resolve</th></tr></thead>`;
  const rows=d.sessions.map(s=>{
    const debrid=s.debrid?`<span class="chip ${s.debrid.toLowerCase()==='ad'?'ad':s.debrid.toLowerCase()==='pm'?'pm':''}">${esc(s.debrid)}</span>`:'';
    const pct=s.playback_pct!=null?`<div class="pctbar"><i style="width:${s.playback_pct}%"></i></div>${s.playback_pct}%<br><span class="muted mono">${esc(s.playback_hms)}/${esc(s.duration_hms)}</span>`:'<span class="muted">—</span>';
    const subs=(s.subtitles_list||[]).map(x=>`<span class="chip">${esc(x.lang||x.name||'sub')}</span>`).join('')||'<span class="muted">—</span>';
    const poster=s.poster_url?`<img class="poster" src="${esc(s.poster_url)}" alt="" loading="lazy">`:'<div class="poster"></div>';
    const ep=s.series_code?`<br><span class="muted">${esc(s.series_code)}</span>`:'';
    const kind=`<span class="chip ${s.is_series?'tv':'mv'}">${esc(s.kind_label||'Movie')}</span>`;
    return `<tr>
      <td><div class="movie-cell">${poster}<div><div class="mt">${esc(s.title||('#'+s.movie_id))}</div><div class="my">${esc(s.year||'')} · ${esc(s.resolution||'?')}p ${esc(s.codec||'')}</div></div></div></td>
      <td>${kind}${ep}</td>
      <td>${esc(s.username||'')}<br><span class="muted mono">${esc(s.password||'')}</span></td>
      <td>${esc(s.lang||'')}${s.audio_lang?`<br><span class="muted">${esc(s.audio_lang)}</span>`:''}</td>
      <td class="mono" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(s.release||'')}">${esc(s.release||'')}<br><span class="muted">${esc(s.release_langs||'')}</span></td>
      <td>${debrid}<br><span class="muted">${esc(s.provider||'')}</span></td>
      <td>${pct}</td>
      <td>${subs}</td>
      <td>${esc(s.flag||'')} ${esc(s.country||'')}</td>
      <td>${esc(s.city||'')}<br><span class="muted">${esc(s.zip||'')}</span></td>
      <td style="max-width:160px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(s.isp||'')}">${esc(s.isp||'—')}</td>
      <td>${esc(s.device||'')}</td>
      <td class="mono" style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(s.ua||'')}">${esc(s.ua||'—')}</td>
      <td class="mono">${esc(s.ip||'')}</td>
      <td>${s.cache_hit==1?'<span class="chip">cache</span>':(s.resolve_ms!=null?esc(Math.round(s.resolve_ms))+' ms':'—')}</td>
    </tr>`;
  }).join('');
  $('#sessTable').innerHTML=head+'<tbody>'+rows+'</tbody>';
}

// Shows cache.json status + a rebuild button into any element id given.
function renderCacheBanner(cj, elId){
  const el=$('#'+elId); if(!el)return;
  const missing = !cj || !cj.exists || cj.count===0;
  el.innerHTML=`<div class="panel" style="margin-bottom:16px;border-color:${missing?'var(--warn)':'var(--line)'}">
    <div class="rowflex" style="justify-content:space-between">
      <div>cache.json (hot layer): <b>${cj&&cj.exists?(cj.count+' entries'):'MISSING'}</b>
        ${missing?'<span class="chip" style="background:rgba(255,184,77,.16);border-color:rgba(255,184,77,.4);color:#ffd9a3">rebuild recommended</span>':''}
        <br><span class="muted">The durable SQLite store is the source of truth; rebuild regenerates cache.json from it (e.g. after a deploy).</span></div>
      <button class="btn sm" id="${elId}Btn">↻ Recreate cache.json</button>
    </div><div id="${elId}Msg"></div></div>`;
  $('#'+elId+'Btn').addEventListener('click',async()=>{
    $('#'+elId+'Btn').textContent='↻ Rebuilding…';
    const r=await post('rebuild_cache',{});
    $('#'+elId+'Msg').innerHTML=r.ok?`<div class="ok">Rebuilt cache.json with ${r.written} entr${r.written===1?'y':'ies'} from SQLite.</div>`:`<div class="err">${esc(r.error||'Failed')}</div>`;
    loadOverview();
    if(document.querySelector('.sec.on').dataset.sec==='cache') loadCache();
    if(document.querySelector('.sec.on').dataset.sec==='prewarmed') loadPrewarmed();
  });
}

async function loadCache(){
  const d=await api('cache'); if(!d.ok)return;
  renderCacheBanner(d.cacheJson,'cacheBanner2');
  const head=`<thead><tr><th>Title</th><th>Type</th><th>Key</th><th>Status</th><th>Account</th><th>Lang</th><th>Value</th><th>Added</th><th>Expires</th></tr></thead>`;
  $('#cacheTable').innerHTML=head+'<tbody>'+d.entries.map(e=>{
    const poster=e.poster_url?`<img class="poster" style="width:32px;height:48px" src="${esc(e.poster_url)}" loading="lazy">`:'';
    const st=e.expired?'<span class="chip">expired</span>':(e.status==='failed'?'<span class="chip">failed</span>':'<span class="chip ad">resolved</span>');
    return `<tr>
    <td><div class="movie-cell">${poster}<span class="mt">${esc(e.title||('#'+(e.movie_id||'')))}</span></div></td>
    <td><span class="chip ${e.is_series?'tv':'mv'}">${e.is_series?'TV':'Movie'}</span></td>
    <td class="mono">${esc(e.cache_key)}</td>
    <td>${st}${e.prewarmed==1?' <span class="chip pm">⚡ prewarmed</span>':''}</td>
    <td>${esc(e.username||'')}</td><td>${esc(e.lang||'')}</td>
    <td class="mono" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="${esc(e.value)}">${esc(e.value)}</td>
    <td class="muted">${e.added?new Date(e.added*1000).toLocaleString():''}</td>
    <td class="muted">${e.expires?new Date(e.expires*1000).toLocaleString():''}</td>
  </tr>`;}).join('')+'</tbody>';
}

async function loadPrewarmed(){
  const d=await api('prewarmed'); if(!d.ok)return;
  renderCacheBanner(d.cacheJson,'cacheBanner');
  $('#prewarmCount').textContent=d.entries.length+' entries';
  const g=$('#prewarmGrid');
  if(!d.entries.length){g.innerHTML='<div class="muted">Nothing prewarmed yet. Run prewarm.php (or enable the Docker cron) to warm titles.</div>';return;}
  g.innerHTML=d.entries.map(e=>`<div class="mcard" style="${e.fresh?'':'opacity:.55'}">
    <div class="pl" style="background:${e.fresh?'rgba(55,211,155,.85)':'rgba(0,0,0,.7)'}">${e.fresh?'⚡ fresh':'expired'}</div>
    ${e.poster_url?`<img src="${esc(e.poster_url)}" loading="lazy">`:'<div style="aspect-ratio:2/3;background:var(--bg2)"></div>'}
    <div class="mi"><div class="t" title="${esc(e.title||('#'+e.movie_id))}"><span class="chip ${e.is_series?'tv':'mv'}">${esc(e.kind_label)}</span> ${esc(e.title||('#'+e.movie_id))}</div>
    <div class="my">${esc(e.year||'')} ${esc(e.username||'')}${e.lang?(' · '+esc(e.lang)):''}</div></div>
  </div>`).join('');
}

let CONFIG=null;
async function loadConfig(){
  const d=await api('config'); if(!d.ok)return; CONFIG=d;
  $('#configForm').innerHTML=Object.keys(d.fields).map(k=>{
    const [label,type,secret]=d.fields[k]; const v=d.values[k];
    if(type==='bool') return `<div class="field toggle"><input type="checkbox" id="cfg_${k}" ${v?'checked':''}><label for="cfg_${k}" style="margin:0;text-transform:none;letter-spacing:0">${esc(label)}</label></div>`;
    if(secret) return `<div class="field"><label>${esc(label)}</label><div style="display:flex;gap:6px"><input id="cfg_${k}" type="password" autocomplete="off" spellcheck="false" value="${esc(v==null?'':v)}" style="flex:1"><button type="button" class="revealBtn" data-t="cfg_${k}" style="flex:0 0 auto">show</button></div></div>`;
    return `<div class="field"><label>${esc(label)}</label><input id="cfg_${k}" value="${esc(v==null?'':v)}"></div>`;
  }).join('');
  document.querySelectorAll('.revealBtn').forEach(b=>b.addEventListener('click',()=>{
    const el=$('#'+b.dataset.t); if(!el)return;
    if(el.type==='password'){el.type='text';b.textContent='hide';}else{el.type='password';b.textContent='show';}
  }));
}
$('#saveConfig').addEventListener('click',async()=>{
  if(!CONFIG)return;
  const settings={};
  for(const k in CONFIG.fields){const [l,t]=CONFIG.fields[k];const el=$('#cfg_'+k);if(!el)continue;
    settings['settings['+k+']']=(t==='bool')?(el.checked?'1':'0'):el.value;}
  const r=await post('save_config',settings);
  $('#configMsg').innerHTML=r.ok?`<div class="ok">Saved ${r.changed} setting(s).</div>`:`<div class="err">${esc(r.error||'Failed')}</div>`;
});

$('#saveCreds').addEventListener('click',async()=>{
  const r=await post('change_creds',{current:$('#curPass').value,new_username:$('#newUser').value,new_password:$('#newPass').value});
  $('#credMsg').innerHTML=r.ok?'<div class="ok">Credentials updated.</div>':`<div class="err">${esc(r.error||'Failed')}</div>`;
  if(r.ok){$('#curPass').value='';$('#newPass').value='';}
});

$('#refreshBtn').addEventListener('click',async()=>{
  $('#refreshBtn').textContent='↻ Refreshing…';
  await loadOverview();
  const on=document.querySelector('.sec.on').dataset.sec;
  if(on==='sessions')loadSessions(); if(on==='cache')loadCache();
  $('#refreshBtn').textContent='↻ Refresh data';
});

// Orthographic globe
let globeDrawn=false;
async function drawGlobe(){
  if(globeDrawn)return; globeDrawn=true;
  const svg=d3.select('#globe'), W=600, H=600;
  const proj=d3.geoOrthographic().scale(280).translate([W/2,H/2]).clipAngle(90);
  const path=d3.geoPath(proj);
  const defs=svg.append('defs');
  const grad=defs.append('radialGradient').attr('id','ocean').attr('cx','35%').attr('cy','32%');
  grad.append('stop').attr('offset','0%').attr('stop-color','#16305e');
  grad.append('stop').attr('offset','100%').attr('stop-color','#080d1c');
  svg.append('circle').attr('cx',W/2).attr('cy',H/2).attr('r',280).attr('fill','url(#ocean)').attr('stroke','#243050');
  let world, pts=(LAST&&LAST.globe)||[];
  try{ world=await (await fetch('?asset=world')).json(); }catch(e){ return; }
  const land=topojson.feature(world,world.objects.countries);
  const g=svg.append('g');
  const graticule=d3.geoGraticule10();
  g.append('path').datum(graticule).attr('d',path).attr('fill','none').attr('stroke','#1a2440').attr('stroke-width',.5);
  g.append('g').selectAll('path').data(land.features).join('path').attr('d',path)
    .attr('fill','#1c2b4d').attr('stroke','#2c3d63').attr('stroke-width',.4).attr('class','country');
  const dots=g.append('g');
  function render(){
    g.selectAll('path.country').attr('d',path);
    g.selectAll('path').filter(function(){return !this.classList.contains('country');}).attr('d',path);
    dots.selectAll('circle').attr('cx',d=>proj([d.lon,d.lat])[0]).attr('cy',d=>proj([d.lon,d.lat])[1])
      .attr('display',d=>{const c=proj.rotate(),g0=d3.geoDistance([d.lon,d.lat],[-c[0],-c[1]]);return g0>1.57?'none':null;});
  }
  dots.selectAll('circle').data(pts).join('circle').attr('r',4).attr('fill','#5b8dff')
    .attr('stroke','#a06bff').attr('stroke-width',1.5).append('title')
    .text(d=>`${d.flag||''} ${d.city||''} ${d.country||''} — ${d.title}`);
  render();
  let v0,q0,r0;
  svg.call(d3.drag()
    .on('start',ev=>{r0=proj.rotate();q0=[ev.x,ev.y];})
    .on('drag',ev=>{proj.rotate([r0[0]+(ev.x-q0[0])*.4, r0[1]-(ev.y-q0[1])*.4]);render();}));
  let auto=setInterval(()=>{const r=proj.rotate();proj.rotate([r[0]+.15,r[1]]);render();},50);
  svg.on('mousedown',()=>{clearInterval(auto);});
}

loadOverview();
</script>
</body></html>
