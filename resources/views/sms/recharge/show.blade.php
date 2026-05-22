@extends('sms.layouts.site')
@section('title', __('sms.recharge.show_title', ['sn' => $recharge->recharge_sn]))
@section('body_class','recharge-page')
@section('content')
@php
    $statusKey = 'sms.status.' . $recharge->status;
    $statusText = __($statusKey);
    if ($statusText === $statusKey) { $statusText = $recharge->status; }
@endphp
<section class="recharge-detail-workspace">
    <div class="container recharge-detail-container">
        <div class="recharge-detail-card">
            <div class="modal-kicker">{{ __('sms.recharge.show_eyebrow') }}</div>
            <h1 class="mono">{{ $recharge->recharge_sn }}</h1>
            <p>{{ __('sms.recharge.modal_sub') }}</p>

            <div class="modal-summary-grid detail-summary-grid">
                <div><span>{{ __('sms.recharge.pay_amount') }}</span><b>¥{{ number_format((float)$recharge->amount,2) }}</b></div>
                <div><span>{{ __('sms.recharge.credit_amount') }}</span><b>¥{{ number_format((float)$recharge->total_amount,2) }}</b></div>
                <div><span>{{ __('sms.sms.payment_method') }}</span><b>{{ $recharge->method_code }} / {{ $recharge->driver }}</b></div>
                <div><span>{{ __('sms.common.status') }}</span><b>{{ $statusText }}</b></div>
            </div>

            @if($recharge->status === 'pending')
                <div class="modal-deadline">{{ __('sms.recharge.pay_before', ['time' => optional($recharge->expires_at)->toDateTimeString()]) }}</div>
                <div class="modal-actions detail-actions">
                    <a class="btn btn-dark" href="{{ route('sms.recharge.index') }}">{{ __('sms.recharge.change_selection') }}</a>
                    <a class="btn btn-primary" href="{{ route('sms.pay.recharge.gateway', ['paymentSn'=>$recharge->payment_sn]) }}">{{ __('sms.recharge.continue_pay') }}</a>
                </div>
            @elseif($recharge->status === 'paid')
                <div class="ok recharge-detail-alert">{{ __('sms.recharge.paid_credited', ['time' => optional($recharge->paid_at)->toDateTimeString()]) }}</div>
                <a class="btn btn-primary btn-block" href="{{ route('sms.index') }}">{{ __('sms.recharge.go_get_number') }}</a>
            @else
                <div class="err recharge-detail-alert">{{ __('sms.recharge.unpayable') }}</div>
                <a class="btn btn-dark btn-block" href="{{ route('sms.recharge.index') }}">{{ __('sms.recharge.retry') }}</a>
            @endif
        </div>
    </div>
</section>
@endsection
