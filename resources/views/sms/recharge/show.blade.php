@extends('sms.layouts.site')
@section('title', __('sms.recharge.show_title', ['sn' => $recharge->recharge_sn]))
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">{{ __('sms.recharge.show_eyebrow') }}</div>
        <h1 class="mono" style="font-size:34px;margin:0 0 12px">{{ $recharge->recharge_sn }}</h1>
        <div class="grid" style="margin:22px 0">
            <div class="stat"><b>¥{{ number_format((float)$recharge->amount,2) }}</b><span>{{ __('sms.recharge.pay_amount') }}</span></div>
            <div class="stat"><b>¥{{ number_format((float)$recharge->total_amount,2) }}</b><span>{{ __('sms.recharge.credit_amount') }}</span></div>
        </div>
        @php
            $statusKey = 'sms.status.' . $recharge->status;
            $statusText = __($statusKey);
            if ($statusText === $statusKey) { $statusText = $recharge->status; }
        @endphp
        <p><span class="muted">{{ __('sms.order.payment_method') }}：</span>{{ $recharge->method_code }} / {{ $recharge->driver }}</p>
        <p><span class="muted">{{ __('sms.common.status') }}：</span><span class="status">{{ $statusText }}</span></p>
        @if($recharge->status === 'pending')
            <p class="muted">{{ __('sms.recharge.pay_before', ['time' => optional($recharge->expires_at)->toDateTimeString()]) }}</p>
            <a class="btn btn-primary btn-block" href="{{ route('sms.pay.recharge.gateway', ['paymentSn'=>$recharge->payment_sn]) }}">{{ __('sms.order.pay_now') }}</a>
        @elseif($recharge->status === 'paid')
            <div class="ok" style="width:100%;margin:18px 0">{{ __('sms.recharge.paid_credited', ['time' => optional($recharge->paid_at)->toDateTimeString()]) }}</div>
            <a class="btn btn-primary btn-block" href="{{ route('sms.index') }}">{{ __('sms.recharge.go_get_number') }}</a>
        @else
            <div class="err" style="width:100%;margin:18px 0">{{ __('sms.recharge.unpayable') }}</div>
            <a class="btn btn-dark btn-block" href="{{ route('sms.recharge.index') }}">{{ __('sms.recharge.retry') }}</a>
        @endif
    </div>
</section>
@endsection
