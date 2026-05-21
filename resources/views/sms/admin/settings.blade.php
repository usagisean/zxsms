@extends('sms.admin.layout')
@section('title','系统配置')
@section('subtitle','当前推荐使用“本地库存”模式：后台导入手机号和取码链接，前台只展示本站商品。')
@section('content')
<form method="post">@csrf
    <div class="card">
        <div class="section-title"><h2>上游模式</h2><span class="badge">Provider</span></div>
        <p class="help">选择 <b>本地库存</b> 后，用户只能购买你后台导入的号码库存；真实取码链接加密保存在服务器端，不会出现在前台订单页。</p>
        <div class="grid">
            <div class="form-row">
                <label>接码上游模式</label>
                <select name="sms_provider">
                    <option value="inventory" @if(($values['sms_provider'] ?? 'inventory') === 'inventory') selected @endif>本地库存 / 站内发货（推荐）</option>
                    <option value="herosms" @if(($values['sms_provider'] ?? 'inventory') === 'herosms') selected @endif>HeroSMS 自动上游（旧模式）</option>
                </select>
            </div>
            <div></div>
            <div class="form-row"><label>HeroSMS Base URL</label><input name="herosms_base_url" value="{{ $values['herosms_base_url'] }}"></div>
            <div class="form-row"><label>HeroSMS API Key（留空不修改）</label><input name="herosms_api_key" value="" placeholder="已保存时不会明文显示"></div>
        </div>
    </div>


    <div class="card">
        <div class="section-title"><h2>站点与联系方式</h2><span class="badge">前台展示</span></div>
        <p class="help">这里控制前台品牌、页脚文案、TG 客服和万人交流群入口。TG 可以填完整链接，也可以直接填用户名，例如 <code>@your_support</code>。</p>
        <div class="grid3">
            <div class="form-row"><label>站点名称</label><input name="site_name" value="{{ $values['site_name'] }}" placeholder="ZXAIHUB SMS"></div>
            <div class="form-row"><label>站点域名/副标题</label><input name="site_domain" value="{{ $values['site_domain'] }}" placeholder="zxaihub.com"></div>
            <div class="form-row"><label>页脚说明</label><input name="site_footer_desc" value="{{ $values['site_footer_desc'] }}" placeholder="长效接码 / 余额购买 / 订单可查"></div>
            <div class="form-row"><label>TG 客服链接</label><input name="support_tg_url" value="{{ $values['support_tg_url'] }}" placeholder="https://t.me/your_support 或 @your_support"></div>
            <div class="form-row"><label>TG 客服按钮文字</label><input name="support_tg_label" value="{{ $values['support_tg_label'] }}" placeholder="TG 客服"></div>
            <div class="form-row"><label>万人交流群链接</label><input name="community_tg_url" value="{{ $values['community_tg_url'] }}" placeholder="https://t.me/your_group 或 @your_group"></div>
            <div class="form-row"><label>交流群按钮文字</label><input name="community_tg_label" value="{{ $values['community_tg_label'] }}" placeholder="万人交流群"></div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><h2>长效接码商品设置</h2><span class="badge">30 天以上 / 默认 60 天</span></div>
        <p class="help">你主要售卖长效号，这里会影响前台介绍文案、后台导入库存的默认有效期，以及前台可售库存过滤。低于“最短展示天数”的号码不会上架出售。</p>
        <div class="grid3">
            <div class="form-row"><label>默认有效天数</label><input name="product_validity_days" value="{{ $values['product_validity_days'] }}" placeholder="60"></div>
            <div class="form-row"><label>最短展示天数</label><input name="product_min_validity_days" value="{{ $values['product_min_validity_days'] }}" placeholder="30"></div>
            <div class="form-row"><label>长效说明</label><input name="product_long_term_note" value="{{ $values['product_long_term_note'] }}" placeholder="本站主售 30 天以上长效接码号，常规库存约 60 天有效。"></div>
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
