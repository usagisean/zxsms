@extends('sms.admin.layout')
@section('title','HeroSMS 后台')
@section('subtitle','独立接码站后台；充值、接码和原发卡网订单完全分离。')
@section('actions')<a class="btn secondary" href="{{ route('sms.index') }}">打开前台</a><a class="btn" href="{{ route('sms.admin.prices') }}">同步价格</a>@endsection
@section('content')
<div class="stat-grid">
    <div class="stat"><span>服务</span><b>{{ $counts['services'] }}</b></div>
    <div class="stat"><span>国家</span><b>{{ $counts['countries'] }}</b></div>
    <div class="stat"><span>可售价格</span><b>{{ $counts['available_prices'] }}</b></div>
    <div class="stat"><span>接码订单</span><b>{{ $counts['orders'] }}</b></div>
    <div class="stat"><span>充值订单</span><b>{{ $counts['recharges'] }}</b></div>
    <div class="stat"><span>等待验证码</span><b>{{ $counts['waiting_code'] }}</b></div>
    <div class="stat"><span>需人工退款</span><b>{{ $counts['refund_required'] }}</b></div>
    <div class="stat"><span>已自动退余额</span><b>{{ $counts['wallet_refunded'] }}</b></div>
</div>
<div class="card" style="margin-top:18px">
    <div class="section-title"><h2>运营说明</h2><span class="badge">Balance Mode</span></div>
    <p class="help">用户优先充值到余额，购买号码时实时确认 HeroSMS 成本并扣余额；取号失败、无码、无库存或超时会自动退回余额。统一价格规则在“配置”里设置，服务/国家的覆盖规则只有填写后才生效。</p>
</div>
@endsection
