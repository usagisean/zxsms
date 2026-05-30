<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>@yield('title','ZXAIHUB SMS 后台')</title>
<style>
:root{color-scheme:dark;--bg:#090d14;--bg2:#0d121b;--panel:#111722;--panel2:#161d2a;--ink:#f7f8fb;--soft:#c8cfdd;--muted:#8892a6;--line:rgba(255,255,255,.10);--line2:rgba(255,255,255,.16);--accent:#8b5cf6;--accent2:#a78bfa;--blue:#3b82f6;--mint:#34d399;--rose:#fb7185;--ease:cubic-bezier(.2,.8,.2,1)}
*{box-sizing:border-box}html,body{min-height:100%}body{margin:0;min-height:100dvh;background:radial-gradient(circle at 12% -8%,rgba(139,92,246,.16),transparent 30%),linear-gradient(180deg,var(--bg),#070a10 80%);color:var(--ink);font-family:"Geist","Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif;font-size:14px;letter-spacing:-.01em}a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}
.admin-shell{display:grid;grid-template-columns:248px minmax(0,1fr);gap:22px;min-height:100dvh;padding:18px}.sidebar{position:sticky;top:18px;height:calc(100dvh - 36px);border:1px solid var(--line);border-radius:22px;background:rgba(13,18,27,.88);overflow:hidden}.sidebar-inner{height:100%;padding:18px;overflow:auto}.brand{display:flex;align-items:center;gap:12px;margin-bottom:22px}.brand-logo{width:42px;height:42px;border-radius:13px;background:linear-gradient(135deg,#7c3aed,#a78bfa);display:grid;place-items:center;font-weight:800}.brand-title b{display:block;font-size:16px;letter-spacing:-.03em}.brand-title span{display:block;margin-top:3px;color:var(--muted);font-size:10px;letter-spacing:.16em;text-transform:uppercase}.nav{display:grid;gap:6px}.nav-group-label{font-size:11px;font-weight:800;color:var(--muted);letter-spacing:.12em;padding:16px 12px 6px;text-transform:uppercase}.nav a{display:flex;align-items:center;justify-content:space-between;min-height:36px;padding:8px 12px;border-radius:12px;color:#d7deec;font-size:13px;font-weight:650;border:1px solid transparent;transition:background .18s var(--ease),border-color .18s var(--ease),color .18s var(--ease)}.nav a:hover,.nav a.active{background:rgba(139,92,246,.13);border-color:rgba(139,92,246,.28);color:#fff}.main{min-width:0;padding:8px 8px 46px}.topbar{display:flex;align-items:flex-end;justify-content:space-between;gap:18px;margin:4px 0 18px}.topbar h1{font-size:clamp(28px,3vw,38px);line-height:1.08;margin:0;letter-spacing:-.045em}.topbar p{margin:8px 0 0;color:var(--soft);line-height:1.6}.top-actions{display:flex;gap:8px;flex-wrap:wrap;justify-content:flex-end}.card,.subcard,.stat{border-radius:18px;background:rgba(17,23,34,.78);border:1px solid var(--line);padding:20px;margin-bottom:16px}.subcard{padding:18px}.card h1,.card h2,.card h3{margin-top:0;letter-spacing:-.03em}.section-title{display:flex;align-items:flex-end;justify-content:space-between;gap:14px;margin-bottom:16px}.section-title h2{margin:0;font-size:22px}.muted{color:var(--soft)}.danger{color:#fecdd3;background:rgba(251,113,133,.10);border-color:rgba(251,113,133,.24)}.small{font-size:12px}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}.ok{border-radius:14px;padding:12px 14px;margin-bottom:16px;background:rgba(52,211,153,.10);border:1px solid rgba(52,211,153,.24);color:#d1fae5;font-weight:700}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.grid4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.form-row{display:grid;gap:7px}.form-row label,label.form-label{font-size:12px;font-weight:750;color:#eef2ff}input,select,textarea{width:100%;min-width:0;min-height:42px;padding:10px 12px;border:1px solid var(--line2);border-radius:11px;background:rgba(255,255,255,.055);color:#fff;outline:none;transition:border-color .18s var(--ease),box-shadow .18s var(--ease),background .18s var(--ease)}input::placeholder,textarea::placeholder{color:#7f8799}input:focus,select:focus,textarea:focus{border-color:rgba(167,139,250,.74);box-shadow:0 0 0 4px rgba(139,92,246,.14);background:rgba(255,255,255,.07)}input[type=checkbox]{width:16px;height:16px;min-width:16px;min-height:16px;accent-color:#ec4899;vertical-align:middle}.check{display:inline-flex;align-items:center;gap:8px;font-weight:700;color:#eef2ff}.btn,button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:40px;background:#7c3aed;color:#fff;border:0;border-radius:11px;padding:9px 14px;cursor:pointer;text-decoration:none;font-weight:750;white-space:nowrap;transition:transform .18s var(--ease),background .18s var(--ease),opacity .18s var(--ease)}.btn:hover,button:hover{transform:translateY(-1px);background:#8b5cf6}.btn:active,button:active{transform:translateY(0)}.btn.secondary{background:rgba(255,255,255,.06);border:1px solid var(--line2)}.btn.small,button.small{padding:7px 11px;min-height:34px;font-size:12px}.table-wrap{width:100%;overflow-x:auto;border-radius:15px;border:1px solid var(--line);background:rgba(255,255,255,.025)}.table{width:100%;border-collapse:separate;border-spacing:0;font-size:13px;min-width:940px}.table th{position:sticky;top:0;background:#121824;color:#fff;font-size:12px;font-weight:750}.table th,.table td{border-bottom:1px solid rgba(255,255,255,.075);padding:13px 12px;text-align:left;vertical-align:top}.table tr:last-child td{border-bottom:0}.table tbody tr:hover{background:rgba(255,255,255,.025)}.rule-grid{display:grid;grid-template-columns:repeat(4,minmax(128px,1fr));gap:9px;margin-top:12px}.rule-grid input{min-height:38px;padding:8px 10px}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:750;background:rgba(139,92,246,.14);color:#ddd6fe;border:1px solid rgba(139,92,246,.24)}.stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.stat{padding:18px}.stat span{display:block;color:var(--soft);font-size:12px}.stat b{display:block;font-size:clamp(24px,2.7vw,30px);margin-top:8px;letter-spacing:-.05em}.stat small{display:block;margin-top:8px;color:var(--muted);font-size:12px}.pagination,nav[role="navigation"]{margin-top:16px}.pagination{display:flex;align-items:center;gap:6px;flex-wrap:wrap;padding:0;list-style:none}.pagination li{list-style:none}.pagination a,.pagination span,nav[role="navigation"] a,nav[role="navigation"] span{display:inline-flex;align-items:center;justify-content:center;min-width:34px;height:34px;padding:0 10px;border-radius:10px;border:1px solid var(--line);background:rgba(255,255,255,.05);color:#e9edf7;text-decoration:none;font-weight:700}.pagination .active span{background:#7c3aed;color:#fff;border-color:#7c3aed}.pagination .disabled span{opacity:.45}.pagination svg,nav[role="navigation"] svg{width:16px!important;height:16px!important;max-width:16px!important;max-height:16px!important}nav[role="navigation"] p{color:var(--soft)}nav[role="navigation"]>div{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}nav[role="navigation"] .hidden{display:flex!important}.help{line-height:1.75;color:#c4c9dc}.help code{background:rgba(255,255,255,.07);border:1px solid var(--line);padding:2px 6px;border-radius:7px;color:#fff}.empty{padding:24px;text-align:center;color:var(--soft)}pre{white-space:pre-wrap;word-break:break-word;max-width:560px;margin:0;background:rgba(0,0,0,.20);border:1px solid var(--line);padding:12px;border-radius:12px;color:#dfe5f8}.reveal-ready,.reveal-ready.is-visible{opacity:1;transform:none}.notice-grid,.workflow-grid,.quick-grid,.mode-strip,.payment-grid{display:grid;gap:14px}.notice-grid{grid-template-columns:repeat(2,minmax(0,1fr));margin:16px 0}.notice-card{display:grid;gap:7px;border-radius:18px;padding:18px;border:1px solid rgba(251,191,36,.22);background:linear-gradient(135deg,rgba(251,191,36,.12),rgba(139,92,246,.08));transition:transform .18s var(--ease),border-color .18s var(--ease)}.notice-card:hover{transform:translateY(-1px);border-color:rgba(251,191,36,.38)}.notice-card strong{font-size:16px}.notice-card span{color:#fef3c7;line-height:1.6}.notice-card.warn{border-color:rgba(251,113,133,.30);background:linear-gradient(135deg,rgba(251,113,133,.14),rgba(139,92,246,.08))}.workflow-grid{grid-template-columns:repeat(4,minmax(0,1fr));margin-top:16px}.workflow-grid .card{display:flex;flex-direction:column;gap:10px}.workflow-grid .card .btn{margin-top:auto}.quick-grid{grid-template-columns:repeat(4,minmax(0,1fr))}.quick-grid a{display:grid;gap:7px;border:1px solid var(--line);border-radius:15px;padding:16px;background:rgba(255,255,255,.035);transition:transform .18s var(--ease),border-color .18s var(--ease),background .18s var(--ease)}.quick-grid a:hover{transform:translateY(-1px);border-color:rgba(139,92,246,.35);background:rgba(139,92,246,.10)}.quick-grid b{font-size:16px}.quick-grid span{color:var(--soft);line-height:1.55}.mode-strip{grid-template-columns:repeat(4,minmax(0,1fr));margin-top:16px}.mode-strip div{border:1px solid var(--line);border-radius:15px;padding:15px;background:rgba(255,255,255,.035)}.mode-strip b,.mode-strip span{display:block}.mode-strip span{margin-top:6px;color:var(--soft);font-size:12px}.payment-grid{grid-template-columns:repeat(2,minmax(0,1fr))}details summary{list-style:none;cursor:pointer}details summary::-webkit-details-marker{display:none}.payment-method summary,.advanced-card>summary{display:flex;align-items:center;justify-content:space-between;gap:12px}.payment-method summary b,.advanced-card summary b{display:block;font-size:16px}.payment-method summary small,.advanced-card summary small{display:block;margin-top:4px;color:var(--muted);font-size:12px;line-height:1.45}.payment-method summary em,.advanced-card summary em{font-style:normal;border:1px solid var(--line);border-radius:999px;padding:5px 9px;color:#ddd6fe;background:rgba(139,92,246,.12);font-size:12px;font-weight:800}.payment-fields,.advanced-body{margin-top:16px;padding-top:16px;border-top:1px solid var(--line)}.advanced-section{margin-top:24px}.settings-actions{position:sticky;bottom:14px;z-index:4;margin-top:18px;padding:12px;border:1px solid var(--line);border-radius:16px;background:rgba(9,13,20,.86);backdrop-filter:blur(16px)}
@media(max-width:1180px){.admin-shell{grid-template-columns:1fr}.sidebar{position:relative;top:0;height:auto}.sidebar-inner{height:auto}.nav{grid-template-columns:repeat(3,minmax(0,1fr))}.nav-group-label{grid-column:1/-1}.main{padding:4px 0 42px}.stat-grid,.grid4,.workflow-grid,.quick-grid,.mode-strip,.payment-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.rule-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.admin-shell{padding:10px}.topbar{display:block}.top-actions{justify-content:flex-start;margin-top:14px}.nav{grid-template-columns:repeat(2,minmax(0,1fr))}.grid,.grid3,.grid4,.stat-grid,.rule-grid,.notice-grid,.workflow-grid,.quick-grid,.mode-strip,.payment-grid{grid-template-columns:1fr}.card{padding:16px;border-radius:16px}.table{min-width:760px}.topbar h1{font-size:28px}.sidebar-inner{padding:14px}.brand-logo{width:38px;height:38px}.settings-actions{position:static}}
</style>
</head>
<body>
@php
$navGroups = [
    '核心业务' => [
        ['label'=>'运营看板','route'=>'sms.admin.dashboard'],
        ['label'=>'库存导入','route'=>'sms.admin.inventory'],
        ['label'=>'商品管理','route'=>'sms.admin.prices'],
        ['label'=>'订单管理','route'=>'sms.admin.orders'],
    ],
    '财务用户' => [
        ['label'=>'用户管理','route'=>'sms.admin.users'],
        ['label'=>'充值订单','route'=>'sms.admin.recharges'],
        ['label'=>'余额流水','route'=>'sms.admin.wallet-logs'],
    ],
    '设置' => [
        ['label'=>'站点配置','route'=>'sms.admin.settings'],
        ['label'=>'首页轮播','route'=>'sms.admin.home-slides'],
        ['label'=>'充值档位','route'=>'sms.admin.recharge-plans'],
    ]
];
@endphp
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="brand"><div class="brand-logo">✦</div><div class="brand-title"><b>ZXAIHUB SMS</b><span>Admin Console</span></div></div>
            <nav class="nav">
                @foreach($navGroups as $groupName => $items)
                    <div class="nav-group-label">{{ $groupName }}</div>
                    @foreach($items as $item)
                        <a class="{{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><span>{{ $item['label'] }}</span></a>
                    @endforeach
                @endforeach
                <div class="nav-group-label">其它</div>
                <a href="{{ route('sms.index') }}" target="_blank">前台 ↗</a>
            </nav>
        </div>
    </aside>
    <main class="main">
        <div class="topbar">
            <div><h1>@yield('title','后台')</h1><p>@yield('subtitle','独立接码站管理，不影响原发卡网订单。')</p></div>
            <div class="top-actions">@yield('actions')</div>
        </div>
        @if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
        @if(isset($errors) && $errors->any())<div class="card danger">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>
</div>
<script>
(function(){
    var targets=[].slice.call(document.querySelectorAll('.card,.subcard,.stat,.table-wrap'));
    if('IntersectionObserver' in window){
        var io=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('is-visible');io.unobserve(entry.target);}});},{threshold:.1,rootMargin:'0px 0px -6% 0px'});
        targets.forEach(function(el,idx){el.classList.add('reveal-ready');el.style.transitionDelay=Math.min(idx%5*55,260)+'ms';io.observe(el);});
        setTimeout(function(){targets.forEach(function(el){el.classList.add('is-visible');});}, 120);
    }else{targets.forEach(function(el){el.classList.add('is-visible');});}
})();
</script>
</body>
</html>
