<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<title>@yield('title','ZXAIHUB SMS 后台')</title>
<style>
:root{color-scheme:dark;--void:#05060a;--ink:#f7f6ff;--soft:#bbc2d9;--muted:#7d849b;--panel:#0d0f17;--panel2:#151823;--hair:rgba(255,255,255,.11);--violet:#8b6cff;--violet2:#c1a9ff;--blue:#6bdcff;--mint:#72f0b5;--rose:#ff6d8b;--spring:cubic-bezier(.32,.72,0,1);--heavy:cubic-bezier(.16,1,.3,1);--ambient:0 34px 120px rgba(0,0,0,.38),inset 0 1px 1px rgba(255,255,255,.12)}
*{box-sizing:border-box}html,body{min-height:100%}body{margin:0;min-height:100dvh;background:var(--void);color:var(--ink);font-family:"Geist","Plus Jakarta Sans",ui-sans-serif,system-ui,sans-serif;font-size:15px;letter-spacing:-.015em}body:before{content:"";position:fixed;inset:0;z-index:-2;pointer-events:none;background:radial-gradient(circle at 12% 6%,rgba(139,108,255,.24),transparent 34%),radial-gradient(circle at 82% 0,rgba(107,220,255,.13),transparent 32%),linear-gradient(180deg,#05060a 0%,#0b0d14 48%,#11131b 100%)}body:after{content:"";position:fixed;inset:0;z-index:-1;pointer-events:none;opacity:.22;background-image:linear-gradient(rgba(255,255,255,.035) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.025) 1px,transparent 1px);background-size:54px 54px;mask-image:linear-gradient(to bottom,rgba(0,0,0,.72),transparent 78%)}
a{color:inherit;text-decoration:none}button,input,select,textarea{font:inherit}.admin-shell{display:grid;grid-template-columns:292px minmax(0,1fr);gap:22px;min-height:100dvh;padding:18px}.sidebar{position:sticky;top:18px;height:calc(100dvh - 36px);padding:8px;border-radius:34px;background:linear-gradient(180deg,rgba(255,255,255,.105),rgba(255,255,255,.035));border:1px solid var(--hair);box-shadow:var(--ambient);overflow:hidden}.sidebar-inner{height:100%;border-radius:26px;background:linear-gradient(180deg,rgba(15,17,26,.97),rgba(7,8,12,.99));padding:20px;overflow:auto;box-shadow:inset 0 1px 1px rgba(255,255,255,.12)}.brand{display:flex;align-items:center;gap:12px;margin-bottom:26px}.brand-logo{width:50px;height:50px;border-radius:18px;background:linear-gradient(135deg,#9678ff,#4d2ed6);display:grid;place-items:center;box-shadow:0 18px 52px rgba(139,108,255,.32),inset 0 1px 1px rgba(255,255,255,.35);font-weight:1000}.brand-title b{display:block;font-size:20px;letter-spacing:-.045em}.brand-title span{display:block;margin-top:4px;color:var(--muted);font-size:11px;letter-spacing:.16em;text-transform:uppercase}.nav{display:grid;gap:8px}.nav a{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:13px 14px;border-radius:18px;color:#dce1f3;font-weight:880;border:1px solid transparent;background:transparent;transition:transform .7s var(--spring),background .7s var(--spring),border-color .7s var(--spring),color .7s var(--spring)}.nav a:hover,.nav a.active{transform:translateX(3px);background:rgba(139,108,255,.14);border-color:rgba(193,169,255,.22);color:#fff}.main{min-width:0;padding:14px 10px 54px}.topbar{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;margin:4px 0 24px}.topbar h1{font-size:clamp(34px,4vw,58px);line-height:.95;margin:0;letter-spacing:-.07em}.topbar p{margin:10px 0 0;color:var(--soft);line-height:1.65}.top-actions{display:flex;gap:10px;flex-wrap:wrap;justify-content:flex-end}.card,.subcard,.stat{position:relative;border-radius:34px;padding:8px;background:linear-gradient(180deg,rgba(255,255,255,.105),rgba(255,255,255,.035));border:1px solid var(--hair);box-shadow:var(--ambient);margin-bottom:20px}.card:before,.subcard:before,.stat:before{content:"";position:absolute;inset:8px;border-radius:26px;background:linear-gradient(180deg,rgba(18,21,32,.97),rgba(8,9,14,.985));box-shadow:inset 0 1px 1px rgba(255,255,255,.12);z-index:0}.card>* ,.subcard>* ,.stat>*{position:relative;z-index:1}.card{padding:28px}.subcard{padding:24px}.card h1,.card h2,.card h3{margin-top:0;letter-spacing:-.04em}.section-title{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:18px}.section-title h2{margin:0;font-size:26px}.muted{color:var(--soft)}.danger{color:var(--rose)}.small{font-size:12px}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}.ok{position:relative;border-radius:24px;padding:17px 18px;margin-bottom:20px;background:rgba(114,240,181,.12);border:1px solid rgba(114,240,181,.26);color:#dfffee;box-shadow:var(--ambient)}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}.grid4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.form-row{display:grid;gap:8px}.form-row label,label.form-label{font-size:12px;text-transform:uppercase;letter-spacing:.14em;font-weight:850;color:#ece9ff}input,select,textarea{width:100%;min-width:0;min-height:48px;padding:12px 14px;border:1px solid rgba(255,255,255,.13);border-radius:16px;background:rgba(255,255,255,.065);color:#fff;outline:none;transition:border-color .7s var(--spring),box-shadow .7s var(--spring),transform .7s var(--spring)}input::placeholder,textarea::placeholder{color:#747b91}input:focus,select:focus,textarea:focus{border-color:rgba(193,169,255,.82);box-shadow:0 0 0 5px rgba(139,108,255,.15);transform:translateY(-1px)}input[type=checkbox]{width:18px;height:18px;min-width:18px;accent-color:#ff5fb6;vertical-align:middle}.check{display:inline-flex;align-items:center;gap:8px;font-weight:850;color:#eef1fb}.btn,button{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:46px;background:linear-gradient(135deg,var(--violet),var(--violet2));color:white;border:0;border-radius:999px;padding:11px 17px;cursor:pointer;text-decoration:none;font-weight:920;white-space:nowrap;box-shadow:0 18px 54px rgba(139,108,255,.22),inset 0 1px 1px rgba(255,255,255,.22);transition:transform .7s var(--spring),background .7s var(--spring),opacity .7s var(--spring)}.btn:hover,button:hover{transform:translateY(-2px)}.btn:active,button:active{transform:scale(.985)}.btn.secondary{background:rgba(255,255,255,.075);border:1px solid rgba(255,255,255,.13);box-shadow:inset 0 1px 1px rgba(255,255,255,.08)}.btn.small,button.small{padding:8px 13px;min-height:38px;font-size:13px}.table-wrap{width:100%;overflow-x:auto;border-radius:26px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.035)}.table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;min-width:940px}.table th{position:sticky;top:0;background:#10131d;color:#faf9ff;font-size:11px;text-transform:uppercase;letter-spacing:.16em}.table th,.table td{border-bottom:1px solid rgba(255,255,255,.075);padding:15px 14px;text-align:left;vertical-align:top}.table tr:last-child td{border-bottom:0}.table tbody tr{transition:background .7s var(--spring)}.table tbody tr:hover{background:rgba(255,255,255,.035)}.rule-grid{display:grid;grid-template-columns:repeat(5,minmax(128px,1fr));gap:10px;margin-top:14px}.rule-grid input{min-height:44px;padding:10px 12px}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:900;background:rgba(139,108,255,.14);color:#d9d0ff;border:1px solid rgba(193,169,255,.22)}.stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}.stat{padding:28px}.stat span{display:block;color:var(--soft);font-size:12px;letter-spacing:.14em;text-transform:uppercase}.stat b{display:block;font-size:38px;margin-top:12px;letter-spacing:-.06em}.pagination,nav[role="navigation"]{margin-top:18px}.pagination{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:0;list-style:none}.pagination li{list-style:none}.pagination a,.pagination span,nav[role="navigation"] a,nav[role="navigation"] span{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.065);color:#e9edfa;text-decoration:none;font-weight:850}.pagination .active span{background:linear-gradient(135deg,var(--violet),var(--violet2));color:#fff;border-color:transparent}.pagination .disabled span{opacity:.45}.pagination svg,nav[role="navigation"] svg{width:18px!important;height:18px!important;max-width:18px!important;max-height:18px!important}nav[role="navigation"] p{color:var(--soft)}nav[role="navigation"]>div{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}nav[role="navigation"] .hidden{display:flex!important}.help{line-height:1.85;color:#c4c9dc}.help code{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.08);padding:2px 7px;border-radius:8px;color:#fff}.empty{padding:30px;text-align:center;color:var(--soft)}pre{white-space:pre-wrap;word-break:break-word;max-width:560px;margin:0;background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.08);padding:12px;border-radius:16px;color:#dfe5f8}.reveal-ready{opacity:0;transform:translateY(34px);filter:blur(9px);transition:opacity .9s var(--spring),transform .9s var(--spring),filter .9s var(--spring)}.reveal-ready.is-visible{opacity:1;transform:translateY(0);filter:blur(0)}
@media(max-width:1180px){.admin-shell{grid-template-columns:1fr}.sidebar{position:relative;top:0;height:auto}.sidebar-inner{height:auto}.nav{grid-template-columns:repeat(2,minmax(0,1fr))}.main{padding:8px 0 42px}.stat-grid,.grid4{grid-template-columns:repeat(2,minmax(0,1fr))}.rule-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:760px){.admin-shell{padding:10px}.topbar{display:block}.top-actions{justify-content:flex-start;margin-top:16px}.nav{grid-template-columns:1fr}.grid,.grid3,.grid4,.stat-grid,.rule-grid{grid-template-columns:1fr}.card{padding:20px;border-radius:28px}.table{min-width:760px}.topbar h1{font-size:38px}}
</style>
</head>
<body>
@php
$navItems = [
    ['label'=>'概览','route'=>'sms.admin.dashboard'],
    ['label'=>'配置','route'=>'sms.admin.settings'],
    ['label'=>'服务管理','route'=>'sms.admin.services'],
    ['label'=>'国家管理','route'=>'sms.admin.countries'],
    ['label'=>'价格','route'=>'sms.admin.prices'],
    ['label'=>'首页轮播','route'=>'sms.admin.home-slides'],
    ['label'=>'充值档位','route'=>'sms.admin.recharge-plans'],
    ['label'=>'充值订单','route'=>'sms.admin.recharges'],
    ['label'=>'余额流水','route'=>'sms.admin.wallet-logs'],
    ['label'=>'接码订单','route'=>'sms.admin.orders'],
    ['label'=>'API 日志','route'=>'sms.admin.logs'],
];
@endphp
<div class="admin-shell">
    <aside class="sidebar">
        <div class="sidebar-inner">
            <div class="brand"><div class="brand-logo">✦</div><div class="brand-title"><b>ZXAIHUB SMS</b><span>Admin Console</span></div></div>
            <nav class="nav">
                @foreach($navItems as $item)
                    <a class="{{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><span>{{ $item['label'] }}</span></a>
                @endforeach
                <a href="{{ route('sms.index') }}">前台</a>
            </nav>
        </div>
    </aside>
    <main class="main">
        <div class="topbar">
            <div><h1>@yield('title','后台')</h1><p>@yield('subtitle','独立接码站管理，不影响原发卡网订单。')</p></div>
            <div class="top-actions">@yield('actions')</div>
        </div>
        @if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
        @if($errors->any())<div class="card danger">{{ $errors->first() }}</div>@endif
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
