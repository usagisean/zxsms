@extends('sms.layouts.site')
@section('title', '充值订单 '.$recharge->recharge_sn.' - ZXAIHUB SMS')
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">💳 充值订单</div>
        <h1 class="mono" style="font-size:34px;margin:0 0 12px">{{ $recharge->recharge_sn }}</h1>
        <div class="grid" style="margin:22px 0">
            <div class="stat"><b>¥{{ number_format((float)$recharge->amount,2) }}</b><span>支付金额</span></div>
            <div class="stat"><b>¥{{ number_format((float)$recharge->total_amount,2) }}</b><span>到账金额</span></div>
        </div>
        <p><span class="muted">支付方式：</span>{{ $recharge->method_code }} / {{ $recharge->driver }}</p>
        <p><span class="muted">状态：</span><span class="status">{{ $recharge->status }}</span></p>
        @if($recharge->status === 'pending')
            <p class="muted">请在 {{ optional($recharge->expires_at)->toDateTimeString() }} 前完成支付。</p>
            <a class="btn btn-primary btn-block" href="{{ route('sms.pay.recharge.gateway', ['paymentSn'=>$recharge->payment_sn]) }}">立即支付</a>
        @elseif($recharge->status === 'paid')
            <div class="ok" style="width:100%;margin:18px 0">已支付并入账：{{ optional($recharge->paid_at)->toDateTimeString() }}</div>
            <a class="btn btn-primary btn-block" href="{{ route('sms.index') }}">去获取号码</a>
        @else
            <div class="err" style="width:100%;margin:18px 0">该充值订单已不可支付，请重新创建。</div>
            <a class="btn btn-dark btn-block" href="{{ route('sms.recharge.index') }}">重新充值</a>
        @endif
    </div>
</section>
@endsection
