<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', __('sms.home.title'))</title>
    <style>
        :root{color-scheme:dark;--bg:#111820;--card:#18212c;--line:#2f3a49;--text:#e8edf5;--muted:#9aa7b8;--accent:#5dd6a0;--danger:#ff6b6b;--white:#fff}
        *{box-sizing:border-box}body{margin:0;background:linear-gradient(180deg,#0f1620,#111820);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Arial,"PingFang SC","Microsoft Yahei",sans-serif;color:var(--text)}
        a{color:var(--accent);text-decoration:none}.wrap{max-width:980px;margin:0 auto;padding:28px 18px}.nav{display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}.brand{font-size:22px;font-weight:700}.nav a{margin-left:14px;color:#cdd6e5}
        .card{background:rgba(24,33,44,.96);border:1px solid var(--line);border-radius:18px;padding:22px;margin-bottom:18px;box-shadow:0 18px 40px rgba(0,0,0,.18)}
        .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.grid3{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}@media(max-width:760px){.grid,.grid3{grid-template-columns:1fr}}
        label{display:block;margin-bottom:8px;color:#cdd6e5;font-weight:600}select,input,button,textarea{width:100%;border:1px solid #405066;background:#111820;color:var(--text);border-radius:12px;padding:13px 14px;font-size:16px}button,.btn{display:inline-flex;align-items:center;justify-content:center;border:0;background:var(--white);color:#111820;border-radius:14px;padding:14px 18px;font-weight:700;cursor:pointer}.btn-dark{background:#243246;color:var(--text);border:1px solid #405066}.btn-danger{background:#5a2430;color:#fff}.muted{color:var(--muted)}.price{font-size:34px;font-weight:800;color:var(--accent)}.err{background:#3a2026;color:#ffd6d6;border:1px solid #74333f;border-radius:12px;padding:12px}.ok{background:#19392f;color:#d7ffef;border:1px solid #2a765d;border-radius:12px;padding:12px}.row{display:flex;gap:12px;flex-wrap:wrap}.pill{border:1px solid #405066;border-radius:999px;padding:8px 12px;color:#cdd6e5}.copy{max-width:180px}.mono{font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace}.pay-method{display:flex;align-items:center;gap:10px;border:1px solid #405066;border-radius:14px;padding:14px;cursor:pointer}.pay-method input{width:auto}.table{width:100%;border-collapse:collapse}.table th,.table td{border-bottom:1px solid #2f3a49;padding:10px;text-align:left}.small{font-size:13px}.hide{display:none}
    </style>
</head>
<body>
<div class="wrap">
    <div class="nav">
        <a class="brand" href="{{ route('sms.index') }}">ZXAIHUB SMS</a>
        <div><a href="{{ route('sms.query') }}">{{ __('sms.nav.query') }}</a><a href="/sms-admin">{{ __('sms.nav.admin') }}</a></div>
    </div>
    @if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
    @if(session('quote_changed'))<div class="err">{{ session('quote_changed') }} · {{ __('sms.common.new_price') }}：¥{{ session('new_price') }}</div>@endif
    @if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif
    @yield('content')
</div>
@yield('scripts')
</body>
</html>
