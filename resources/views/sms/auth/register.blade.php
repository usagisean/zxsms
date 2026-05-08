@extends('sms.layouts.site')
@section('title', __('sms.auth.register_title'))
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">{{ __('sms.auth.register_eyebrow') }}</div>
        <h1 style="font-size:42px;margin:0 0 12px">{{ __('sms.auth.register_headline') }}</h1>
        <p class="muted" style="line-height:1.7">{{ __('sms.auth.register_sub') }}</p>
        <form method="post" action="{{ route('register.post') }}" style="margin-top:24px">
            @csrf
            <div class="field"><label>{{ __('sms.auth.name_optional') }}</label><input type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('sms.auth.name_placeholder') }}"></div>
            <div class="field"><label>{{ __('sms.auth.email') }}</label><input type="email" name="email" value="{{ old('email') }}" required placeholder="you@example.com"></div>
            <div class="field"><label>{{ __('sms.auth.password') }}</label><input type="password" name="password" required placeholder="{{ __('sms.auth.password_min') }}"></div>
            <div class="field"><label>{{ __('sms.auth.password_confirm') }}</label><input type="password" name="password_confirmation" required placeholder="{{ __('sms.auth.password_confirm_placeholder') }}"></div>
            <button class="btn btn-primary btn-block" type="submit">{{ __('sms.auth.register_btn') }}</button>
        </form>
        <div class="mini-links"><span>{{ __('sms.auth.have_account') }}</span><a href="{{ route('login') }}" style="color:var(--accent2);font-weight:900">{{ __('sms.auth.go_login') }}</a></div>
    </div>
</section>
@endsection
