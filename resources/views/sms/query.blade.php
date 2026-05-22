@extends('sms.layouts.site')
@section('title', __('sms.query.title'))
@section('body_class','query-page')
@section('content')
@php
    $hasResults = $orders !== null;
    $resultCount = $hasResults ? $orders->count() : 0;
@endphp
<section class="query-workspace">
    <div class="container query-container">
        <div class="query-shell {{ $hasResults ? 'has-results' : '' }}">
            <div class="query-copy-card">
                <div class="eyebrow query-eyebrow">{{ __('sms.query.eyebrow') }}</div>
                <h1>{{ __('sms.query.headline') }}</h1>
                <p>{{ __('sms.query.sub') }}</p>
                <div class="query-quick-actions">
                    @auth
                        <a class="btn btn-primary" href="{{ route('sms.account.numbers') }}">{{ __('sms.nav.my_numbers') }}</a>
                        <a class="btn btn-dark" href="{{ route('sms.index') }}">{{ __('sms.nav.get_number') }}</a>
                    @else
                        <a class="btn btn-primary" href="{{ route('login') }}">{{ __('sms.nav.login') }}</a>
                        <a class="btn btn-dark" href="{{ route('register') }}">{{ __('sms.nav.register') }}</a>
                    @endauth
                </div>
            </div>

            <form method="post" action="{{ route('sms.query.post') }}" class="query-form-card">
                @csrf
                <div class="query-section-head">
                    <span class="step-dot">01</span>
                    <h2>{{ __('sms.query.submit') }}</h2>
                </div>
                <div class="query-field-grid">
                    <label class="query-field">
                        <span>{{ __('sms.query.order_sn') }}</span>
                        <input name="order_sn" value="{{ old('order_sn') }}" placeholder="{{ __('sms.query.order_sn_placeholder') }}">
                    </label>
                    <label class="query-field">
                        <span>{{ __('sms.query.email') }}</span>
                        <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" placeholder="{{ __('sms.query.email_placeholder') }}">
                    </label>
                    <label class="query-field">
                        <span>{{ __('sms.query.query_password') }}</span>
                        <input name="query_password" value="{{ old('query_password') }}" placeholder="{{ __('sms.query.query_password_placeholder') }}">
                    </label>
                </div>
                <button class="btn btn-primary btn-block query-submit" type="submit">{{ __('sms.query.submit') }}</button>
            </form>

            @if($hasResults)
                <section class="query-results-card">
                    <div class="query-section-head results-head">
                        <div>
                            <span class="step-dot">02</span>
                            <h2>{{ __('sms.query.results') }}</h2>
                        </div>
                        <span class="account-count">{{ $resultCount }}</span>
                    </div>
                    @if($orders->isEmpty())
                        <div class="query-empty">{{ __('sms.query.empty') }}</div>
                    @else
                        <div class="query-result-scroll">
                            <div class="query-result-grid">
                                @foreach($orders as $order)
                                    @php
                                        $statusKey = 'sms.status.' . $order->status;
                                        $statusText = __($statusKey);
                                        if ($statusText === $statusKey) { $statusText = $order->status; }
                                    @endphp
                                    <article class="query-result-card">
                                        <div class="query-result-top">
                                            <span class="mono">{{ $order->order_sn }}</span>
                                            <span class="status">{{ $statusText }}</span>
                                        </div>
                                        <div class="query-result-main">
                                            <b>{{ $order->service->name ?? $order->service_code }}</b>
                                            <small>{{ $order->country->name ?? $order->country_code }} · {{ optional($order->created_at)->format('m-d H:i') }}</small>
                                        </div>
                                        <div class="query-result-meta">
                                            <span>{{ __('sms.common.phone') }} <b class="mono">{{ $order->phone_number ?: '-' }}</b></span>
                                            <a class="btn btn-dark btn-compact" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">{{ __('sms.common.view') }}</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @endif
        </div>
    </div>
</section>
@endsection
