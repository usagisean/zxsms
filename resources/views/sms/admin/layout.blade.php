<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title','ZXAIHUB SMS 后台')</title>
<style>
:root{--bg:#090b12;--panel:#10131d;--panel2:#151927;--line:#262b3a;--text:#f3f6ff;--muted:#9aa3b7;--brand:#8b5cf6;--blue:#2f7cf6;--green:#30d158;--danger:#ff5c7a;--input:#0d1019}
*{box-sizing:border-box}html,body{min-height:100%}body{margin:0;background:radial-gradient(circle at 8% 0%,rgba(139,92,246,.18),transparent 34%),linear-gradient(180deg,#0b0d15,#080a0f);color:var(--text);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,"PingFang SC","Microsoft Yahei",sans-serif;font-size:15px}.admin-shell{display:grid;grid-template-columns:260px minmax(0,1fr);min-height:100vh}.sidebar{position:sticky;top:0;height:100vh;padding:26px 18px;border-right:1px solid rgba(255,255,255,.08);background:rgba(10,12,19,.82);backdrop-filter:blur(16px);overflow:auto}.brand{display:flex;align-items:center;gap:12px;margin-bottom:26px}.brand-logo{width:44px;height:44px;border-radius:14px;background:linear-gradient(135deg,#6d5dfc,#9b7cff);display:grid;place-items:center;box-shadow:0 14px 40px rgba(139,92,246,.35);font-weight:1000}.brand-title b{display:block;font-size:20px;letter-spacing:.4px}.brand-title span{color:var(--muted);font-size:12px}.nav{display:grid;gap:8px}.nav a{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:12px 14px;border-radius:14px;color:#dfe4f3;text-decoration:none;font-weight:800;border:1px solid transparent}.nav a:hover,.nav a.active{background:rgba(139,92,246,.16);border-color:rgba(139,92,246,.32);color:#fff}.main{min-width:0;padding:28px 34px 54px}.topbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:22px}.topbar h1{font-size:30px;margin:0;letter-spacing:-.8px}.topbar p{margin:7px 0 0;color:var(--muted)}.top-actions{display:flex;gap:10px;flex-wrap:wrap}.card{background:linear-gradient(180deg,rgba(20,24,37,.96),rgba(12,14,22,.96));border:1px solid rgba(255,255,255,.1);border-radius:22px;padding:22px;margin-bottom:18px;box-shadow:0 20px 80px rgba(0,0,0,.24)}.card h1,.card h2,.card h3{margin-top:0}.section-title{display:flex;align-items:flex-end;justify-content:space-between;gap:16px;margin-bottom:16px}.section-title h2{margin:0}.muted{color:var(--muted)}.ok{background:rgba(48,209,88,.12);border:1px solid rgba(48,209,88,.35);padding:13px 15px;border-radius:16px;margin-bottom:18px;color:#c8ffd7}.danger{color:var(--danger)}.small{font-size:12px}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.grid4{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}.form-row{display:grid;gap:7px}.form-row label,label.form-label{font-weight:800;color:#e9edf8}input,select,textarea{width:100%;min-width:0;padding:12px 13px;border:1px solid rgba(180,190,214,.35);border-radius:13px;background:rgba(255,255,255,.075);color:#fff;outline:none;font:inherit}input::placeholder,textarea::placeholder{color:#858da1}input:focus,select:focus,textarea:focus{border-color:#8bbcff;box-shadow:0 0 0 4px rgba(47,124,246,.18)}input[type=checkbox]{width:18px;height:18px;min-width:18px;accent-color:#ff4fb1;vertical-align:middle}.check{display:inline-flex;align-items:center;gap:8px;font-weight:800;color:#edf1fb}.btn,button{display:inline-flex;align-items:center;justify-content:center;gap:8px;background:linear-gradient(135deg,#2f7cf6,#7c5cff);color:white;border:0;border-radius:13px;padding:11px 16px;cursor:pointer;text-decoration:none;font-weight:900;white-space:nowrap;box-shadow:0 14px 34px rgba(47,124,246,.22)}.btn.secondary{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);box-shadow:none}.btn.small,button.small{padding:8px 12px;border-radius:11px;font-size:13px}.table-wrap{width:100%;overflow-x:auto;border-radius:18px;border:1px solid rgba(255,255,255,.08)}.table{width:100%;border-collapse:separate;border-spacing:0;font-size:14px;min-width:920px}.table th{position:sticky;top:0;background:#121622;color:#f7f9ff;font-size:13px;text-transform:uppercase;letter-spacing:.04em}.table th,.table td{border-bottom:1px solid rgba(255,255,255,.08);padding:14px 13px;text-align:left;vertical-align:top}.table tr:last-child td{border-bottom:0}.table tbody tr:hover{background:rgba(255,255,255,.035)}.rule-grid{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:10px;margin-top:12px}.rule-grid input{padding:10px 11px}.badge{display:inline-flex;align-items:center;border-radius:999px;padding:5px 9px;font-size:12px;font-weight:900;background:rgba(139,92,246,.16);color:#cfc4ff;border:1px solid rgba(139,92,246,.28)}.stat-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}.stat{padding:20px;border-radius:20px;background:rgba(255,255,255,.055);border:1px solid rgba(255,255,255,.08)}.stat span{display:block;color:var(--muted);font-size:13px}.stat b{display:block;font-size:30px;margin-top:10px}.admin-pager,.pagination{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:18px 0 0;padding:0;list-style:none}.pagination li{list-style:none}.pagination a,.pagination span,.admin-pager a,.admin-pager span{display:inline-flex;align-items:center;justify-content:center;min-width:38px;height:38px;padding:0 12px;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);color:#e9edfa;text-decoration:none;font-weight:800}.pagination .active span,.admin-pager .active{background:#7c5cff;color:#fff;border-color:#7c5cff}.pagination .disabled span{opacity:.45}.pagination svg,nav[role="navigation"] svg{width:18px!important;height:18px!important;max-width:18px!important;max-height:18px!important}.pagination p,nav[role="navigation"] p{color:var(--muted)}nav[role="navigation"]{margin-top:18px}nav[role="navigation"] > div{display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap}nav[role="navigation"] .hidden{display:flex!important}.inline-form{display:inline}.subcard{background:rgba(255,255,255,.045);border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:16px}.help{line-height:1.8;color:#b9c0d1}.help code{background:rgba(255,255,255,.08);padding:2px 6px;border-radius:7px;color:#fff}.empty{padding:28px;text-align:center;color:var(--muted)}pre{white-space:pre-wrap;word-break:break-word;max-width:520px;margin:0;background:rgba(0,0,0,.22);border:1px solid rgba(255,255,255,.08);padding:10px;border-radius:12px;color:#dfe5f8}@media(max-width:1100px){.admin-shell{grid-template-columns:1fr}.sidebar{position:relative;height:auto}.nav{grid-template-columns:repeat(2,minmax(0,1fr))}.main{padding:22px 16px}.stat-grid,.grid4{grid-template-columns:repeat(2,minmax(0,1fr))}.rule-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:720px){.topbar{display:block}.nav{grid-template-columns:1fr}.grid,.grid3,.grid4,.stat-grid,.rule-grid{grid-template-columns:1fr}.card{padding:16px}.table{min-width:760px}}
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
        <div class="brand"><div class="brand-logo">✦</div><div class="brand-title"><b>ZXAIHUB SMS</b><span>Admin Console</span></div></div>
        <nav class="nav">
            @foreach($navItems as $item)
                <a class="{{ request()->routeIs($item['route']) ? 'active' : '' }}" href="{{ route($item['route']) }}"><span>{{ $item['label'] }}</span></a>
            @endforeach
            <a href="{{ route('sms.index') }}">前台</a>
        </nav>
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
</body>
</html>
