@extends('sms.layouts.site')
@section('title', __('sms.auth.login_title'))
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">{{ __('sms.auth.login_eyebrow') }}</div>
        <h1 style="font-size:42px;margin:0 0 12px">{{ __('sms.auth.login_headline') }}</h1>
        <p class="muted" style="line-height:1.7">{{ __('sms.auth.login_sub') }}</p>
        <form method="post" action="{{ route('login.post') }}" style="margin-top:24px">
            @csrf
            <div class="field"><label>{{ __('sms.auth.email') }}</label><input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="you@example.com"></div>
            <div class="field"><label>{{ __('sms.auth.password') }}</label><input type="password" name="password" required placeholder="{{ __('sms.auth.password') }}"></div>
            <label class="pay-card" style="margin:0 0 20px"><input type="checkbox" name="remember" value="1"><span>{{ __('sms.auth.remember') }}</span></label>
            <button class="btn btn-primary btn-block" type="submit">{{ __('sms.auth.login_btn') }}</button>
        </form>
        <div class="mini-links"><span>{{ __('sms.auth.no_account') }}</span><a href="{{ route('register') }}" style="color:var(--purple2);font-weight:900">{{ __('sms.auth.register_now') }}</a></div>
    </div>
</section>
@endsection
