@extends('sms.layouts.site')
@section('title', __('sms.recharge.title'))
@section('body_class','recharge-page')
@section('content')
@php
    $firstPlan = $plans->first();
    $lastRecharge = $orders->first();
@endphp
<section class="recharge-workspace">
    <div class="container recharge-container">
        <form method="post" action="{{ route('sms.recharge.create') }}" class="recharge-card">
            @csrf
            <div class="recharge-topline">
                <div class="recharge-copy">
                    <div class="eyebrow recharge-eyebrow">{{ __('sms.recharge.eyebrow') }}</div>
                    <h1>{{ __('sms.recharge.headline') }}</h1>
                    <p>{{ __('sms.recharge.sub') }}</p>
                </div>
                <div class="wallet-snapshot" aria-label="{{ __('sms.recharge.current_balance') }}">
                    <span>{{ __('sms.recharge.current_balance') }}</span>
                    <strong>¥{{ number_format((float)$wallet->balance, 2) }}</strong>
                    <div class="wallet-mini-grid">
                        <div><small>{{ __('sms.recharge.total_recharged') }}</small><b>¥{{ number_format((float)$wallet->total_recharged, 2) }}</b></div>
                        <div><small>{{ __('sms.recharge.total_refunded') }}</small><b>¥{{ number_format((float)$wallet->total_refunded, 2) }}</b></div>
                    </div>
                </div>
            </div>

            <div class="recharge-main">
                <div class="recharge-primary">
                    <div class="recharge-section-head">
                        <div>
                            <span class="step-dot">01</span>
                            <h2>{{ __('sms.recharge.choose_plan') }}</h2>
                        </div>
                        @if($firstPlan)
                            <span class="recharge-live-pill" data-credit-preview>到账 ¥{{ number_format((float)$firstPlan->total_amount, 2) }}</span>
                        @endif
                    </div>

                    @if($plans->isEmpty())
                        <div class="empty recharge-empty">{{ __('sms.recharge.no_plans') }}</div>
                    @else
                        <div class="plan-compact-grid">
                            @foreach($plans as $plan)
                                @php
                                    $amount = (float) $plan->amount;
                                    $bonus = (float) $plan->bonus_amount;
                                    $credit = $amount + $bonus;
                                @endphp
                                <label class="recharge-plan-card @if($loop->first) is-selected @endif" data-plan-card data-amount="{{ number_format($amount, 2, '.', '') }}" data-credit="{{ number_format($credit, 2, '.', '') }}" data-name="{{ $plan->name }}">
                                    <input type="radio" name="plan_id" value="{{ $plan->id }}" @if($loop->first) checked @endif>
                                    <span class="plan-check">✓</span>
                                    <span class="plan-amount">¥{{ number_format($amount, 0) }}</span>
                                    <span class="plan-name">{{ $plan->name }}</span>
                                    <span class="plan-tags">
                                        @if($bonus > 0)<em>{{ __('sms.recharge.bonus', ['amount'=>number_format($bonus,2)]) }}</em>@endif
                                        @if($plan->badge)<em>{{ $plan->badge }}</em>@endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <aside class="recharge-aside">
                    <div class="pay-method-panel">
                        <div class="recharge-section-head compact">
                            <div>
                                <span class="step-dot">02</span>
                                <h2>{{ __('sms.sms.payment_method') }}</h2>
                            </div>
                        </div>
                        @if(empty($methods))
                            <div class="err recharge-inline-error">{{ __('sms.recharge.no_methods') }}</div>
                        @else
                            <div class="method-compact-grid">
                                @foreach($methods as $code => $method)
                                    <label class="method-chip @if($loop->first) is-selected @endif" data-method-card>
                                        <input type="radio" name="payment_method" value="{{ $code }}" @if($loop->first) checked @endif>
                                        <span class="method-icon">{{ mb_substr($method['name'], 0, 1) }}</span>
                                        <span><b>{{ $method['name'] }}</b><small>{{ $method['driver'] }}</small></span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    <div class="checkout-snapshot">
                        <div class="summary-line"><span>{{ __('sms.recharge.pay_amount') }}</span><b data-pay-preview>{{ $firstPlan ? '¥'.number_format((float)$firstPlan->amount, 2) : '¥--' }}</b></div>
                        <div class="summary-line"><span>{{ __('sms.recharge.credit_amount') }}</span><b data-arrive-preview>{{ $firstPlan ? '¥'.number_format((float)$firstPlan->total_amount, 2) : '¥--' }}</b></div>
                        <div class="summary-line muted-line"><span>{{ __('sms.recharge.current_balance') }} + {{ __('sms.recharge.credit_amount') }}</span><b data-after-preview>{{ $firstPlan ? '¥'.number_format((float)$wallet->balance + (float)$firstPlan->total_amount, 2) : '¥--' }}</b></div>
                        <button class="btn btn-primary btn-block recharge-submit" type="submit" @if($plans->isEmpty() || empty($methods)) disabled @endif>{{ __('sms.recharge.create_order') }}</button>
                        @if($lastRecharge)
                            @php
                                $statusKey = 'sms.status.' . $lastRecharge->status;
                                $statusText = __($statusKey);
                                if ($statusText === $statusKey) { $statusText = $lastRecharge->status; }
                            @endphp
                            <a class="recent-inline" href="{{ route('sms.recharge.show', $lastRecharge->token) }}">
                                <span>{{ __('sms.recharge.recent') }}</span>
                                <b>¥{{ number_format((float)$lastRecharge->total_amount, 2) }}</b>
                                <small>{{ $statusText }} · {{ optional($lastRecharge->created_at)->format('m-d H:i') }}</small>
                            </a>
                        @endif
                    </div>
                </aside>
            </div>
        </form>
    </div>
</section>
@endsection
@section('scripts')
<script>
(function(){
    const balance = {{ json_encode((float)$wallet->balance) }};
    const money = value => '¥' + Number(value || 0).toFixed(2);
    const planCards = Array.from(document.querySelectorAll('[data-plan-card]'));
    const methodCards = Array.from(document.querySelectorAll('[data-method-card]'));
    const payPreview = document.querySelector('[data-pay-preview]');
    const arrivePreview = document.querySelector('[data-arrive-preview]');
    const afterPreview = document.querySelector('[data-after-preview]');
    const creditPreview = document.querySelector('[data-credit-preview]');

    function selectCard(cards, active) {
        cards.forEach(card => card.classList.toggle('is-selected', card === active));
        const input = active && active.querySelector('input[type="radio"]');
        if (input) input.checked = true;
    }

    function updatePlan(card) {
        if (!card) return;
        selectCard(planCards, card);
        const amount = Number(card.dataset.amount || 0);
        const credit = Number(card.dataset.credit || amount);
        if (payPreview) payPreview.textContent = money(amount);
        if (arrivePreview) arrivePreview.textContent = money(credit);
        if (afterPreview) afterPreview.textContent = money(balance + credit);
        if (creditPreview) creditPreview.textContent = '到账 ' + money(credit);
    }

    planCards.forEach(card => card.addEventListener('click', () => updatePlan(card)));
    methodCards.forEach(card => card.addEventListener('click', () => selectCard(methodCards, card)));
    updatePlan(planCards.find(card => card.querySelector('input[type="radio"]:checked')) || planCards[0]);
})();
</script>
@endsection
