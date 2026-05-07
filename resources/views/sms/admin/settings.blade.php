@extends('sms.admin.layout')
@section('title','系统配置')
@section('subtitle','HeroSMS、统一定价、支付方式都可以在这里维护；敏感密钥留空表示不修改。')
@section('content')
<form method="post">@csrf
    <div class="card">
        <div class="section-title"><h2>HeroSMS 配置</h2><span class="badge">Provider</span></div>
        <div class="grid">
            <div class="form-row"><label>HeroSMS Base URL</label><input name="herosms_base_url" value="{{ $values['herosms_base_url'] }}"></div>
            <div class="form-row"><label>HeroSMS API Key（留空不修改）</label><input name="herosms_api_key" value="" placeholder="已保存时不会明文显示"></div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><h2>统一价格规则</h2><span class="badge">默认全站生效</span></div>
        <p class="help">售价公式：<code>max(成本USD × 汇率 × 加价倍数 + 固定手续费, 成本CNY + 最低利润, 最低售价)</code>。例如官网成本折算为 ¥2，你想卖 ¥3：设置 <code>加价倍数=1</code>、<code>最低利润=1</code>、<code>最低售价=3</code> 即可。服务/国家里的单独规则只在填写后覆盖这里。</p>
        <div class="grid3">
            <div class="form-row"><label>USD/CNY 汇率</label><input name="pricing_exchange_rate" value="{{ $values['pricing_exchange_rate'] }}" placeholder="7.3"></div>
            <div class="form-row"><label>统一加价倍数</label><input name="pricing_markup_multiplier" value="{{ $values['pricing_markup_multiplier'] }}" placeholder="1"></div>
            <div class="form-row"><label>统一固定手续费</label><input name="pricing_fixed_fee" value="{{ $values['pricing_fixed_fee'] }}" placeholder="0"></div>
            <div class="form-row"><label>统一最低利润</label><input name="pricing_min_profit" value="{{ $values['pricing_min_profit'] }}" placeholder="1"></div>
            <div class="form-row"><label>统一最低售价</label><input name="pricing_min_price" value="{{ $values['pricing_min_price'] }}" placeholder="3"></div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><h2>支付配置</h2><span class="badge">覆盖 .env</span></div>
        <p class="help">密钥字段留空表示不修改。易支付的商户 ID、网关、密钥可以给支付宝/微信共用；USDT 用 Epusdt API 地址和 API Key。</p>
        <div class="grid">
            @foreach($paymentMethods as $code=>$method)
                <div class="subcard">
                    <h3 style="margin-bottom:8px">{{ $method['name'] }}</h3>
                    <p class="muted mono" style="margin-top:0">{{ $code }} / {{ $method['driver'] }}</p>
                    <div class="grid">
                        <label class="check"><input type="checkbox" name="payment_{{ $code }}_enabled" value="1" @if($method['enabled']) checked @endif> 启用</label>
                        <div class="form-row"><label>pay_check / trade_type</label><input name="payment_{{ $code }}_pay_check" value="{{ $method['pay_check'] ?? '' }}"></div>
                        <div class="form-row"><label>商户ID / API Key</label><input name="payment_{{ $code }}_merchant_id" value="{{ $method['merchant_id'] ?? '' }}"></div>
                        <div class="form-row"><label>网关 URL / merchant_key</label><input name="payment_{{ $code }}_merchant_key" value="{{ $method['merchant_key'] ?? '' }}"></div>
                        <div class="form-row"><label>接口地址 endpoint_url</label><input name="payment_{{ $code }}_endpoint_url" value="{{ $method['endpoint_url'] ?? '' }}"></div>
                        <div class="form-row"><label>密钥 merchant_secret</label><input name="payment_{{ $code }}_merchant_secret" value="" placeholder="留空不修改"></div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="top-actions"><button type="submit">保存配置</button><a class="btn secondary" href="{{ route('sms.admin.prices') }}">去同步价格</a></div>
</form>
@endsection
