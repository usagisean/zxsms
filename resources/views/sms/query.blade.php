@extends('sms.layouts.site')
@section('title', __('sms.query.title'))
@section('content')

<section class="section-tight" style="padding-top:4rem;">
    <div class="container" style="max-width: 800px;">
        <div class="checkout-card" style="padding: 3rem;">
            <div style="text-align:center; margin-bottom: 2rem;">
                <div class="eyebrow" style="justify-content:center; margin-bottom: 1rem;">{{ __('sms.query.eyebrow') }}</div>
                <h1 style="font-size:2rem; margin-bottom:1rem;">{{ __('sms.query.headline') }}</h1>
                <p class="muted">{{ __('sms.query.sub') }}</p>
                
                @auth
                    <div style="margin-top:2rem;"><a class="btn btn-primary" href="{{ route('sms.account.numbers') }}">查看我的号码</a></div>
                @else
                    <div style="margin-top:2rem;display:flex;justify-content:center;gap:1rem;"><a class="btn btn-primary" href="{{ route('login') }}">登录后按邮箱查询</a><a class="btn btn-ghost" href="{{ route('register') }}">注册账号</a></div>
                @endauth
            </div>

            <hr style="border:none; border-top:1px solid var(--border-light); margin: 2rem 0;">

            <form method="post" action="{{ route('sms.query.post') }}">
                @csrf
                <div style="display:flex; flex-direction:column; gap: 1.5rem;">
                    <div class="field">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.875rem;">{{ __('sms.query.order_sn') }}</label>
                        <input name="order_sn" value="{{ old('order_sn') }}" placeholder="{{ __('sms.query.order_sn_placeholder') }}" style="width:100%; padding:0.75rem 1rem; border-radius:var(--radius-md); background:rgba(0,0,0,0.2); border:1px solid var(--border-light); color:var(--text-primary);">
                    </div>
                    <div class="field">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.875rem;">{{ __('sms.query.email') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('sms.query.email_placeholder') }}" style="width:100%; padding:0.75rem 1rem; border-radius:var(--radius-md); background:rgba(0,0,0,0.2); border:1px solid var(--border-light); color:var(--text-primary);">
                    </div>
                    <div class="field">
                        <label style="display:block; margin-bottom:0.5rem; color:var(--text-secondary); font-size:0.875rem;">{{ __('sms.query.query_password') }}</label>
                        <input name="query_password" value="{{ old('query_password') }}" placeholder="{{ __('sms.query.query_password_placeholder') }}" style="width:100%; padding:0.75rem 1rem; border-radius:var(--radius-md); background:rgba(0,0,0,0.2); border:1px solid var(--border-light); color:var(--text-primary);">
                    </div>
                    <button class="btn btn-primary" type="submit" style="width:100%; padding:1rem; font-size:1.1rem; margin-top:1rem; justify-content:center;">{{ __('sms.query.submit') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>

@if($orders !== null)
<section class="section-tight" style="padding-top:2rem;">
    <div class="container" style="max-width: 1000px;">
        <div class="checkout-card" style="padding: 2rem;">
            <h2 style="margin-top:0;font-size:1.5rem; margin-bottom:1.5rem;">{{ __('sms.query.results') }}</h2>
            @if($orders->isEmpty())
                <div class="empty" style="text-align:center; padding: 3rem; background:rgba(0,0,0,0.2); border-radius:var(--radius-md);">{{ __('sms.query.empty') }}</div>
            @else
                <div class="table-wrap" style="overflow-x:auto;">
                    <table class="table" style="width:100%; text-align:left; border-collapse:collapse;">
                        <thead>
                            <tr style="border-bottom:1px solid var(--border-light);">
                                <th style="padding:1rem; color:var(--text-secondary); font-weight:600;">{{ __('sms.common.order_no') }}</th>
                                <th style="padding:1rem; color:var(--text-secondary); font-weight:600;">{{ __('sms.common.service') }}</th>
                                <th style="padding:1rem; color:var(--text-secondary); font-weight:600;">{{ __('sms.common.country') }}</th>
                                <th style="padding:1rem; color:var(--text-secondary); font-weight:600;">{{ __('sms.common.status') }}</th>
                                <th style="padding:1rem; color:var(--text-secondary); font-weight:600;">{{ __('sms.common.phone') }}</th>
                                <th style="padding:1rem; color:var(--text-secondary); font-weight:600;">{{ __('sms.common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($orders as $order)
                            @php
                                $statusKey = 'sms.status.' . $order->status;
                                $statusText = __($statusKey);
                                if ($statusText === $statusKey) { $statusText = $order->status; }
                            @endphp
                            <tr style="border-bottom:1px solid rgba(255,255,255,0.05);">
                                <td class="mono" style="padding:1rem;">{{ $order->order_sn }}</td>
                                <td style="padding:1rem;">{{ $order->service->name ?? $order->service_code }}</td>
                                <td style="padding:1rem;">{{ $order->country->name ?? $order->country_code }}</td>
                                <td style="padding:1rem;"><span class="pill" style="font-size:0.75rem; padding:0.25rem 0.5rem; border-radius:var(--radius-pill); background:rgba(255,255,255,0.1);">{{ $statusText }}</span></td>
                                <td class="mono" style="padding:1rem; color:var(--accent-cyan);">{{ $order->phone_number ?: '-' }}</td>
                                <td style="padding:1rem;"><a class="btn btn-dark" style="padding:0.4rem 0.8rem; font-size:0.875rem;" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">{{ __('sms.common.view') }}</a></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</section>
@endif
@endsection
