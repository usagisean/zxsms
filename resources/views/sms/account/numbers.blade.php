@extends('sms.layouts.site')
@section('title', __('sms.account.title'))
@section('body_class','account-page')
@section('content')
@php
    $displayName = auth()->user()->name ?: auth()->user()->email;
    $ordersTotal = method_exists($orders, 'total') ? $orders->total() : $orders->count();
    $latestOrder = $orders->getCollection()->first();
@endphp
<section class="account-workspace">
    <div class="container account-container">
        <div class="account-shell">
            <div class="account-hero">
                <div class="account-copy">
                    <div class="eyebrow account-eyebrow">{{ __('sms.account.eyebrow') }}</div>
                    <h1>{{ __('sms.account.headline', ['name' => $displayName]) }}</h1>
                    <p>{{ __('sms.account.sub') }}</p>
                    <div class="account-actions">
                        <a class="btn btn-primary" href="{{ route('sms.index') }}">{{ __('sms.account.new_number') }}</a>
                        <a class="btn btn-dark" href="{{ route('sms.recharge.index') }}">{{ __('sms.nav.recharge') }}</a>
                        <a class="btn btn-ghost" href="{{ route('sms.query') }}">{{ __('sms.nav.query') }}</a>
                    </div>
                </div>
                <div class="account-stats-card">
                    <div class="account-stat primary">
                        <span>{{ __('sms.recharge.current_balance') }}</span>
                        <strong>¥{{ number_format((float)$wallet->balance,2) }}</strong>
                    </div>
                    <div class="account-stat-grid">
                        <div class="account-stat"><span>{{ __('sms.account.spent') }}</span><strong>¥{{ number_format((float)$wallet->total_spent,2) }}</strong></div>
                        <div class="account-stat"><span>{{ __('sms.account.refunded') }}</span><strong>¥{{ number_format((float)$wallet->total_refunded,2) }}</strong></div>
                    </div>
                </div>
            </div>

            <div class="account-main-grid">
                <section class="account-panel account-orders-panel">
                    <div class="account-section-head">
                        <div>
                            <span class="step-dot">01</span>
                            <h2>{{ __('sms.account.sms_orders') }}</h2>
                        </div>
                        <span class="account-count">{{ $ordersTotal }}</span>
                    </div>

                    @if($orders->isEmpty())
                        <div class="account-empty">
                            <b>{{ __('sms.account.empty_orders') }}</b>
                            <a class="btn btn-primary" href="{{ route('sms.index') }}">{{ __('sms.account.new_number') }}</a>
                        </div>
                    @else
                        <div class="account-scroll">
                            <div class="number-card-grid">
                                @foreach($orders as $order)
                                    @php
                                        $statusKey = 'sms.status.' . $order->status;
                                        $statusText = __($statusKey);
                                        if ($statusText === $statusKey) { $statusText = $order->status; }
                                        $serviceName = $order->service->name ?? $order->service_code;
                                        $countryName = $order->country->name ?? $order->country_code;
                                    @endphp
                                    <article class="number-card">
                                        <div class="number-card-top">
                                            <span class="mono">{{ $order->order_sn }}</span>
                                            <span class="status">{{ $statusText }}</span>
                                        </div>
                                        <div class="number-service">
                                            <b>{{ $serviceName }}</b>
                                            <small>{{ $countryName }} · ¥{{ number_format((float)$order->sale_price,2) }}</small>
                                        </div>
                                        <div class="number-data-grid">
                                            <div>
                                                <span>{{ __('sms.common.phone') }}</span>
                                                <b class="mono">{{ $order->phone_number ?: '-' }}</b>
                                            </div>
                                            <div>
                                                <span>{{ __('sms.common.code') }}</span>
                                                <b class="mono code-value">{{ $order->sms_code ?: '-' }}</b>
                                            </div>
                                        </div>
                                        <div class="number-card-bottom">
                                            <small>
                                                {{ optional($order->created_at)->format('m-d H:i') }}
                                                @if($order->wallet_refunded_at)
                                                    · {{ __('sms.account.refunded_amount', ['amount' => number_format((float)$order->wallet_amount,2)]) }}
                                                @endif
                                            </small>
                                            <a class="btn btn-dark btn-compact" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">{{ __('sms.common.view') }}</a>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                        @if($orders->hasPages())
                            <div class="compact-pagination">{{ $orders->onEachSide(1)->links() }}</div>
                        @endif
                    @endif
                </section>

                <aside class="account-activity-stack">
                    <section class="account-panel compact-panel">
                        <div class="mini-head">
                            <h3>{{ __('sms.account.recent_recharge') }}</h3>
                            @if($recharges->isNotEmpty())<span>{{ $recharges->count() }}</span>@endif
                        </div>
                        @if($recharges->isEmpty())
                            <div class="mini-empty">{{ __('sms.recharge.no_records') }}</div>
                        @else
                            <div class="activity-list">
                                @foreach($recharges->take(5) as $r)
                                    @php
                                        $statusKey = 'sms.status.' . $r->status;
                                        $statusText = __($statusKey);
                                        if ($statusText === $statusKey) { $statusText = $r->status; }
                                    @endphp
                                    <a class="activity-item" href="{{ route('sms.recharge.show',$r->token) }}">
                                        <span><b class="mono">{{ $r->recharge_sn }}</b><small>{{ $statusText }} · {{ optional($r->created_at)->format('m-d H:i') }}</small></span>
                                        <strong>¥{{ number_format((float)$r->total_amount,2) }}</strong>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </section>

                    <section class="account-panel compact-panel">
                        <div class="mini-head">
                            <h3>{{ __('sms.account.wallet_logs') }}</h3>
                            @if($logs->isNotEmpty())<span>{{ $logs->count() }}</span>@endif
                        </div>
                        @if($logs->isEmpty())
                            <div class="mini-empty">{{ __('sms.common.none') }}</div>
                        @else
                            <div class="activity-list wallet-list">
                                @foreach($logs->take(6) as $log)
                                    <div class="activity-item">
                                        <span><b>{{ $log->type }}</b><small>{{ \Illuminate\Support\Str::limit($log->remark, 42) }} · {{ optional($log->created_at)->format('m-d H:i') }}</small></span>
                                        <strong class="{{ (float)$log->amount >= 0 ? 'positive' : 'negative' }}">{{ (float)$log->amount >= 0 ? '+' : '' }}{{ number_format((float)$log->amount,2) }}</strong>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </section>
                </aside>
            </div>
        </div>
    </div>
</section>
@endsection
