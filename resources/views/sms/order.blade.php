@extends('sms.layouts.site')
@section('title', __('sms.order.title', ['sn' => $order->order_sn]))
@php
$statusKey = 'sms.status.' . $order->status;
$statusText = __($statusKey);
if ($statusText === $statusKey) { $statusText = $order->status; }
$payment = $order->latestPayment;
$donePay = !empty($order->paid_at);
$donePhone = !empty($order->phone_number);
$doneCode = !empty($order->sms_code);
@endphp
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="order-head">
                <div>
                    <div class="eyebrow">📦 接码工作台 · 已在站内发货</div>
                    <h1 style="font-size:clamp(32px,4vw,56px);margin:0 0 12px" class="mono">{{ $order->order_sn }}</h1>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <span class="pill">{{ $order->service->name ?? $order->service_code }}</span>
                        <span class="pill">{{ $order->country->name ?? $order->country_code }}</span>
                        <span class="status">{{ $statusText }}</span>
                    </div>
                </div>
                <div style="text-align:right">
                    <div class="muted">{{ __('sms.order.amount') }}</div>
                    <div class="price">¥{{ number_format((float)$order->sale_price, 2) }}</div>
                </div>
            </div>
            @if($order->status_note)<p class="err" style="width:100%;margin:22px 0 0">{{ $order->status_note }}</p>@endif
            <div class="order-timeline">
                <div class="time-step done"><b>1. {{ __('sms.order.quoted') }}</b><br><span>{{ __('sms.order.quoted_desc') }}</span></div>
                <div class="time-step {{ $donePay ? 'done' : '' }}"><b>2. {{ __('sms.order.pay_step') }}</b><br><span>{{ $donePay ? optional($order->paid_at)->format('H:i:s') : __('sms.order.waiting_pay') }}</span></div>
                <div class="time-step {{ $donePhone ? 'done' : '' }}"><b>3. {{ __('sms.order.number_step') }}</b><br><span>{{ $donePhone ? __('sms.order.number_done') : __('sms.order.number_pending') }}</span></div>
                <div class="time-step {{ $doneCode ? 'done' : '' }}"><b>4. {{ __('sms.order.code_step') }}</b><br><span>{{ $doneCode ? __('sms.order.code_done') : __('sms.order.code_pending') }}</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container grid">
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:30px">订单与扣款</h2>
            @if($payment)
                @php
                    $paymentStatusKey = 'sms.status.' . $payment->status;
                    $paymentStatusText = __($paymentStatusKey);
                    if ($paymentStatusText === $paymentStatusKey) { $paymentStatusText = $payment->status; }
                @endphp
                <p><span class="muted">{{ __('sms.order.payment_method') }}：</span>{{ $payment->method_code }} / {{ $paymentStatusText }}</p>
                <p><span class="muted">{{ __('sms.order.payment_sn') }}：</span><span class="mono">{{ $payment->payment_sn }}</span></p>
                @if($order->status === 'wait_pay' && $payment->status === 'pending')
                    <p class="muted">{{ __('sms.order.pay_before', ['time' => optional($order->expires_at)->toDateTimeString()]) }}</p>
                    <a class="btn btn-primary btn-block" href="{{ route('sms.pay.gateway', ['methodCode'=>$payment->method_code, 'paymentSn'=>$payment->payment_sn]) }}">{{ __('sms.order.pay_now') }}</a>
                @elseif($payment->status === 'paid')
                    <div class="ok" style="width:100%;margin:14px 0 0">{{ __('sms.order.paid_at', ['time' => optional($payment->paid_at)->toDateTimeString()]) }}</div>
                @endif
            @else
                @if($order->wallet_paid_at)
                    <p><span class="muted">{{ __('sms.order.payment_method') }}：</span>{{ __('sms.order.wallet_pay') }}</p>
                    <p><span class="muted">{{ __('sms.order.wallet_amount') }}：</span>¥{{ number_format((float)$order->wallet_amount, 2) }}</p>
                    <div class="ok" style="width:100%;margin:14px 0 0">{{ __('sms.order.wallet_paid_at', ['time' => optional($order->wallet_paid_at)->toDateTimeString()]) }}</div>
                    @if($order->wallet_refunded_at)
                        <div class="err" style="width:100%;margin:14px 0 0">{{ __('sms.order.wallet_refunded', ['time' => optional($order->wallet_refunded_at)->toDateTimeString(), 'reason' => $order->wallet_refund_reason]) }}</div>
                    @endif
                @else
                    <p class="muted">{{ __('sms.order.no_payment') }}</p>
                @endif
            @endif
            <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap">
                <a class="btn btn-dark" href="{{ route('sms.index') }}">{{ __('sms.order.buy_again') }}</a>
                <a class="btn btn-ghost" href="{{ route('sms.query') }}">{{ __('sms.order.query_order') }}</a>
            </div>
        </div>

        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">手机号与验证码</h2>
            <p class="muted" style="line-height:1.7;margin-top:-10px">本站会自动刷新短信，验证码出现后可直接复制使用。</p>
            <div class="field">
                <label>{{ __('sms.order.phone') }}</label>
                <div class="copy-row"><input class="mono" id="phone" readonly value="{{ $order->phone_number }}" placeholder="{{ __('sms.order.phone_placeholder') }}"><button type="button" class="btn btn-dark" data-copy="phone">{{ __('sms.order.copy_phone') }}</button></div>
            </div>
            <div class="field">
                <label>{{ __('sms.order.code') }}</label>
                <div class="copy-row"><input class="mono" id="code" readonly value="{{ $order->sms_code }}" placeholder="{{ __('sms.order.code_placeholder') }}"><button type="button" class="btn btn-dark" data-copy="code">{{ __('sms.order.copy_code') }}</button></div>
            </div>
            <div class="field">
                <label>{{ __('sms.order.sms_text') }}</label>
                <textarea id="sms_text" rows="4" readonly placeholder="{{ __('sms.order.sms_text_placeholder') }}">{{ $order->sms_text }}</textarea>
            </div>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
                <button class="btn btn-dark" type="button" id="manual-refresh-code">刷新短信</button>
                <p class="muted" id="polling-text" style="margin:0">{{ __('sms.order.polling') }}</p>
            </div>
        </div>
    </div>
</section>

@if(in_array($order->status, ['waiting_code','purchasing_number']) && empty($order->phone_number))
<section class="section-tight">
    <div class="container">
        <form method="post" action="{{ route('sms.order.cancel', ['token'=>$order->token]) }}" onsubmit="return confirm(@json(__('sms.order.cancel_confirm')))" class="panel panel-black">
            @csrf
            <button class="btn btn-danger" type="submit">{{ __('sms.order.cancel') }}</button>
            <span class="muted" style="margin-left:12px">{{ __('sms.order.cancel_tip') }}</span>
        </form>
    </div>
</section>
@endif
@endsection
@section('scripts')
<script>
const terminal = ['completed','cancelled','expired','refund_required','provider_no_stock','failed','refunded'];
const pollingTemplate = @json(__('sms.order.polling_status'));
function copy(id){const el=document.getElementById(id); if(!el || !el.value)return; navigator.clipboard.writeText(el.value);}
function tpl(text, vars){return Object.keys(vars).reduce((s,k)=>s.replace(':'+k, vars[k]), text);}
document.querySelectorAll('[data-copy]').forEach(btn=>btn.addEventListener('click',()=>copy(btn.dataset.copy)));
async function poll(force){
    try{
        const statusUrl = @json(route('sms.order.status', ['token'=>$order->token]));
        const res = await fetch(statusUrl + (force ? '?force=1' : ''), {headers:{'Accept':'application/json'}});
        const data = await res.json();
        const badge = document.querySelector('.status');
        if(badge) badge.textContent = data.status_text || data.status;
        if(data.phone_number) document.getElementById('phone').value=data.phone_number;
        if(data.sms_code) document.getElementById('code').value=data.sms_code;
        if(data.sms_text) document.getElementById('sms_text').value=data.sms_text;
        document.getElementById('polling-text').textContent=tpl(pollingTemplate, {status:(data.status_text||data.status), time:new Date().toLocaleTimeString()});
        if(!terminal.includes(data.status)) setTimeout(()=>poll(false), 8000);
    }catch(e){ if(!force) setTimeout(()=>poll(false), 12000); }
}
const manualRefresh=document.getElementById('manual-refresh-code');
if(manualRefresh){manualRefresh.addEventListener('click',()=>poll(true));}
@if(!in_array($order->status, ['completed','cancelled','expired','refund_required','provider_no_stock','failed','refunded']))
setTimeout(()=>poll(false), 3000);
@endif
</script>
@endsection
