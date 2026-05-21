@extends('sms.admin.layout')
@section('title','接码站后台')
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
    <p class="help">用户先充值到余额，购买号码时确认库存并扣余额；库存不足、发货失败或未发货会自动退回余额。统一价格和库存在后台维护。</p>
</div>
@endsection
