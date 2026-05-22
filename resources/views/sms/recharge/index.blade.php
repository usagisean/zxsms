@extends('sms.layouts.site')
@section('title', __('sms.recharge.title'))
@section('body_class','recharge-page')
@section('content')
@php
    $firstPlan = $plans->first();
    $firstMethod = empty($methods) ? null : reset($methods);
@endphp
<section class="recharge-workspace">
    <div class="container recharge-container">
        <form method="post" action="{{ route('sms.recharge.create') }}" class="recharge-card" data-recharge-form>
            @csrf
            <div class="recharge-head">
                <div class="recharge-copy">
                    <div class="eyebrow recharge-eyebrow">{{ __('sms.recharge.eyebrow') }}</div>
                    <h1>{{ __('sms.recharge.headline') }}</h1>
                    <p>{{ __('sms.recharge.checkout_hint') }}</p>
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

            <div class="recharge-body">
                <section class="recharge-primary">
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
                </section>

                <section class="pay-method-panel">
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
                                <label class="method-chip @if($loop->first) is-selected @endif" data-method-card data-method-name="{{ $method['name'] }}" data-method-driver="{{ $method['driver'] }}">
                                    <input type="radio" name="payment_method" value="{{ $code }}" @if($loop->first) checked @endif>
                                    <span class="method-icon">{{ mb_substr($method['name'], 0, 1) }}</span>
                                    <span><b>{{ $method['name'] }}</b><small>{{ $method['driver'] }}</small></span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>

            <div class="recharge-checkout-bar">
                <div class="checkout-total">
                    <span>{{ __('sms.recharge.pay_amount') }}</span>
                    <b data-pay-preview>{{ $firstPlan ? '¥'.number_format((float)$firstPlan->amount, 2) : '¥--' }}</b>
                    <small>{{ __('sms.recharge.credit_amount') }} <em data-arrive-preview>{{ $firstPlan ? '¥'.number_format((float)$firstPlan->total_amount, 2) : '¥--' }}</em> · <span data-method-preview>{{ $firstMethod['name'] ?? __('sms.sms.payment_method') }}</span></small>
                </div>
                <button class="btn btn-primary recharge-submit" type="submit" @if($plans->isEmpty() || empty($methods)) disabled @endif data-submit-text="{{ __('sms.recharge.create_order') }}" data-loading-text="{{ __('sms.recharge.creating_order') }}">{{ __('sms.recharge.create_order') }}</button>
                <div class="checkout-after"><span>{{ __('sms.recharge.current_balance') }} + {{ __('sms.recharge.credit_amount') }}</span><b data-after-preview>{{ $firstPlan ? '¥'.number_format((float)$wallet->balance + (float)$firstPlan->total_amount, 2) : '¥--' }}</b></div>
            </div>

            <div class="recharge-form-error" data-recharge-error hidden></div>
        </form>
    </div>
</section>

<div class="recharge-modal" data-recharge-modal hidden>
    <div class="recharge-modal-backdrop" data-modal-close></div>
    <div class="recharge-modal-card" role="dialog" aria-modal="true" aria-labelledby="recharge-modal-title">
        <button class="modal-close" type="button" data-modal-close aria-label="Close">×</button>
        <div class="modal-kicker">{{ __('sms.recharge.show_eyebrow') }}</div>
        <h2 id="recharge-modal-title">{{ __('sms.recharge.modal_title') }}</h2>
        <p>{{ __('sms.recharge.modal_sub') }}</p>
        <div class="modal-order-sn mono" data-modal-sn>RC----</div>
        <div class="modal-summary-grid">
            <div><span>{{ __('sms.recharge.pay_amount') }}</span><b data-modal-pay>¥--</b></div>
            <div><span>{{ __('sms.recharge.credit_amount') }}</span><b data-modal-credit>¥--</b></div>
            <div><span>{{ __('sms.sms.payment_method') }}</span><b data-modal-method>--</b></div>
            <div><span>{{ __('sms.common.status') }}</span><b data-modal-status>--</b></div>
        </div>
        <div class="modal-deadline" data-modal-deadline></div>
        <div class="modal-actions">
            <button class="btn btn-dark" type="button" data-modal-close>{{ __('sms.recharge.change_selection') }}</button>
            <a class="btn btn-primary" href="#" data-modal-pay-link>{{ __('sms.recharge.continue_pay') }}</a>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script>
(function(){
    const balance = {{ json_encode((float)$wallet->balance) }};
    const money = value => '¥' + Number(value || 0).toFixed(2);
    const form = document.querySelector('[data-recharge-form]');
    const planCards = Array.from(document.querySelectorAll('[data-plan-card]'));
    const methodCards = Array.from(document.querySelectorAll('[data-method-card]'));
    const payPreview = document.querySelector('[data-pay-preview]');
    const arrivePreview = document.querySelector('[data-arrive-preview]');
    const afterPreview = document.querySelector('[data-after-preview]');
    const creditPreview = document.querySelector('[data-credit-preview]');
    const methodPreview = document.querySelector('[data-method-preview]');
    const submit = document.querySelector('.recharge-submit');
    const errorBox = document.querySelector('[data-recharge-error]');
    const modal = document.querySelector('[data-recharge-modal]');

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

    function updateMethod(card) {
        if (!card) return;
        selectCard(methodCards, card);
        if (methodPreview) methodPreview.textContent = card.dataset.methodName || card.textContent.trim();
    }

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.hidden = false;
    }

    function clearError() {
        if (!errorBox) return;
        errorBox.textContent = '';
        errorBox.hidden = true;
    }

    function setLoading(loading) {
        if (!submit) return;
        submit.disabled = loading;
        submit.textContent = loading ? submit.dataset.loadingText : submit.dataset.submitText;
    }

    function showModal(order) {
        if (!modal) return;
        modal.querySelector('[data-modal-sn]').textContent = order.recharge_sn || '--';
        modal.querySelector('[data-modal-pay]').textContent = money(order.amount);
        modal.querySelector('[data-modal-credit]').textContent = money(order.total_amount);
        modal.querySelector('[data-modal-method]').textContent = order.method_name || order.method_code || '--';
        modal.querySelector('[data-modal-status]').textContent = order.status_text || order.status || '--';
        modal.querySelector('[data-modal-deadline]').textContent = order.expires_at ? '{{ __('sms.recharge.pay_before', ['time' => '__TIME__']) }}'.replace('__TIME__', order.expires_at) : '';
        modal.querySelector('[data-modal-pay-link]').href = order.payment_url || order.show_url || '#';
        modal.hidden = false;
        document.body.classList.add('modal-open');
    }

    function closeModal() {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('modal-open');
    }

    planCards.forEach(card => card.addEventListener('click', () => updatePlan(card)));
    methodCards.forEach(card => card.addEventListener('click', () => updateMethod(card)));
    document.querySelectorAll('[data-modal-close]').forEach(item => item.addEventListener('click', closeModal));
    document.addEventListener('keydown', event => { if (event.key === 'Escape') closeModal(); });

    if (form) {
        form.addEventListener('submit', async function(event) {
            if (!window.fetch || !window.FormData) return;
            event.preventDefault();
            clearError();
            setLoading(true);
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                });
                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.ok) {
                    const errors = data.errors ? Object.values(data.errors).flat() : [];
                    throw new Error(errors[0] || data.message || '{{ __('sms.recharge.create_failed') }}');
                }
                showModal(data.order);
            } catch (error) {
                showError(error.message || '{{ __('sms.recharge.create_failed') }}');
            } finally {
                setLoading(false);
            }
        });
    }

    updatePlan(planCards.find(card => card.querySelector('input[type="radio"]:checked')) || planCards[0]);
    updateMethod(methodCards.find(card => card.querySelector('input[type="radio"]:checked')) || methodCards[0]);
})();
</script>
@endsection
