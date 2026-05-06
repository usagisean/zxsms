@extends('sms.admin.layout')
@section('title','配置')
@section('content')
<form method="post" class="card">@csrf
<h1>HeroSMS 配置</h1>
<div class="grid"><div><label>HeroSMS Base URL</label><input name="herosms_base_url" value="{{ $values['herosms_base_url'] }}"></div><div><label>HeroSMS API Key（留空不修改）</label><input name="herosms_api_key" value="" placeholder="已保存时不会明文显示"></div></div>
<h2>价格规则</h2>
<div class="grid3"><div><label>USD/CNY 汇率</label><input name="pricing_exchange_rate" value="{{ $values['pricing_exchange_rate'] }}"></div><div><label>默认加价倍数</label><input name="pricing_markup_multiplier" value="{{ $values['pricing_markup_multiplier'] }}"></div><div><label>固定手续费</label><input name="pricing_fixed_fee" value="{{ $values['pricing_fixed_fee'] }}"></div><div><label>最低利润</label><input name="pricing_min_profit" value="{{ $values['pricing_min_profit'] }}"></div><div><label>最低售价</label><input name="pricing_min_price" value="{{ $values['pricing_min_price'] }}"></div></div>
<h2>支付配置</h2><p class="muted">密钥留空表示不修改；字段会覆盖 .env 配置。</p>
@foreach($paymentMethods as $code=>$method)
<div class="card" style="background:#f8fafc"><h3>{{ $method['name'] }} <span class="muted">{{ $code }} / {{ $method['driver'] }}</span></h3><div class="grid3"><label><input type="checkbox" name="payment_{{ $code }}_enabled" value="1" @if($method['enabled']) checked @endif style="width:auto"> 启用</label><div><label>pay_check / trade_type</label><input name="payment_{{ $code }}_pay_check" value="{{ $method['pay_check'] ?? '' }}"></div><div><label>商户ID/API Key</label><input name="payment_{{ $code }}_merchant_id" value="{{ $method['merchant_id'] ?? '' }}"></div><div><label>网关URL/merchant_key</label><input name="payment_{{ $code }}_merchant_key" value="{{ $method['merchant_key'] ?? '' }}"></div><div><label>接口地址 endpoint_url</label><input name="payment_{{ $code }}_endpoint_url" value="{{ $method['endpoint_url'] ?? '' }}"></div><div><label>密钥 merchant_secret（留空不改）</label><input name="payment_{{ $code }}_merchant_secret" value=""></div></div></div>
@endforeach
<p><button type="submit">保存配置</button></p>
</form>
@endsection
