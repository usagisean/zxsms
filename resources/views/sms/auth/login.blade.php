@extends('sms.layouts.site')
@section('title', '邮箱登录 - ZXAIHUB SMS')
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">🔐 邮箱登录</div>
        <h1 style="font-size:42px;margin:0 0 12px">登录后管理我的号码</h1>
        <p class="muted" style="line-height:1.7">邮箱只用于账号登录和订单归档，当前不需要 SMTP 邮件配置。</p>
        <form method="post" action="{{ route('login.post') }}" style="margin-top:24px">
            @csrf
            <div class="field"><label>邮箱</label><input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com"></div>
            <div class="field"><label>密码</label><input type="password" name="password" required placeholder="请输入密码"></div>
            <label class="pay-card" style="margin:0 0 20px"><input type="checkbox" name="remember" value="1"><span>记住登录状态</span></label>
            <button class="btn btn-primary btn-block" type="submit">登录</button>
        </form>
        <div class="mini-links"><span>还没有账号？</span><a href="{{ route('register') }}" style="color:var(--purple2);font-weight:900">立即注册</a></div>
    </div>
</section>
@endsection
