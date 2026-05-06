@extends('sms.layouts.site')
@section('title', '注册账号 - ZXAIHUB SMS')
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">✨ 创建账号</div>
        <h1 style="font-size:42px;margin:0 0 12px">注册 ZXAIHUB SMS</h1>
        <p class="muted" style="line-height:1.7">注册后下单会自动保存到“我的号码”。不强制邮箱验证，不依赖邮件发送。</p>
        <form method="post" action="{{ route('register.post') }}" style="margin-top:24px">
            @csrf
            <div class="field"><label>昵称（可选）</label><input type="text" name="name" value="{{ old('name') }}" placeholder="例如：ZX 用户"></div>
            <div class="field"><label>邮箱</label><input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com"></div>
            <div class="field"><label>密码</label><input type="password" name="password" required placeholder="至少 8 位"></div>
            <div class="field"><label>确认密码</label><input type="password" name="password_confirmation" required placeholder="再次输入密码"></div>
            <button class="btn btn-primary btn-block" type="submit">注册并登录</button>
        </form>
        <div class="mini-links"><span>已有账号？</span><a href="{{ route('login') }}" style="color:var(--purple2);font-weight:900">去登录</a></div>
    </div>
</section>
@endsection
