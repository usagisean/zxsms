@extends('sms.admin.layout')
@section('title','站点配置')
@section('subtitle','只保留当前铺货业务最常改的配置：品牌、TG、长效号规则、支付。旧上游和统一加价规则收进高级配置。')
@section('content')
<form method="post">@csrf
    <div class="card hero-config-card">
        <div class="section-title"><h2>当前业务模式</h2><span class="badge">推荐配置</span></div>
        <p class="help">现在按“本地库存 / 站内发货”运营：你先导入上游买来的长效手机号和取码链接，用户登录充值后用余额购买，订单和接码记录都留在本站。</p>
        <div class="mode-strip">
            <div><b>1. 导入库存</b><span>手机号|取码链接</span></div>
            <div><b>2. 商品上架</b><span>改标题、价格、库存展示</span></div>
            <div><b>3. 用户购买</b><span>余额支付，成功才发货</span></div>
            <div><b>4. 站内查码</b><span>订单、邮箱都能找回</span></div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><h2>品牌与联系方式</h2><span class="badge">前台展示</span></div>
        <p class="help">这里决定前台看起来是谁在运营，以及用户遇到问题时去哪里联系你。TG 可以填完整链接，也可以直接填用户名，例如 <code>@your_support</code>。</p>
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
        <div class="section-title"><h2>长效接码规则</h2><span class="badge">30 天以上 / 默认 60 天</span></div>
        <p class="help">你主要卖 30 天以上、约 60 天有效的长效号。低于“最短展示天数”的库存不会上架，避免用户买到短效号。</p>
        <div class="grid3">
            <div class="form-row"><label>默认有效天数</label><input name="product_validity_days" value="{{ $values['product_validity_days'] }}" placeholder="60"></div>
            <div class="form-row"><label>最短展示天数</label><input name="product_min_validity_days" value="{{ $values['product_min_validity_days'] }}" placeholder="30"></div>
            <div class="form-row"><label>前台长效说明</label><input name="product_long_term_note" value="{{ $values['product_long_term_note'] }}" placeholder="本站主售 30 天以上长效接码号，常规库存约 60 天有效。"></div>
        </div>
    </div>

    <div class="card">
        <div class="section-title"><h2>支付配置</h2><span class="badge">只改实际使用的通道</span></div>
        <p class="help">充值成功后自动入余额。密钥字段留空表示不修改；不使用的支付方式直接关闭即可。</p>
        <div class="payment-grid">
            @foreach($paymentMethods as $code=>$method)
                <details class="subcard payment-method" @if($method['enabled']) open @endif>
                    <summary>
                        <span><b>{{ $method['name'] }}</b><small>{{ $code }} / {{ $method['driver'] }}</small></span>
                        <em>{{ $method['enabled'] ? '已启用' : '未启用' }}</em>
                    </summary>
                    <div class="payment-fields">
                        <label class="check"><input type="checkbox" name="payment_{{ $code }}_enabled" value="1" @if($method['enabled']) checked @endif> 启用此支付方式</label>
                        <div class="grid">
                            <div class="form-row"><label>pay_check / trade_type</label><input name="payment_{{ $code }}_pay_check" value="{{ $method['pay_check'] ?? '' }}"></div>
                            <div class="form-row"><label>商户ID / API Key</label><input name="payment_{{ $code }}_merchant_id" value="{{ $method['merchant_id'] ?? '' }}"></div>
                            <div class="form-row"><label>网关 URL / merchant_key</label><input name="payment_{{ $code }}_merchant_key" value="{{ $method['merchant_key'] ?? '' }}"></div>
                            <div class="form-row"><label>接口地址 endpoint_url</label><input name="payment_{{ $code }}_endpoint_url" value="{{ $method['endpoint_url'] ?? '' }}"></div>
                            <div class="form-row"><label>密钥 merchant_secret</label><input name="payment_{{ $code }}_merchant_secret" value="" placeholder="留空不修改"></div>
                        </div>
                    </div>
                </details>
            @endforeach
        </div>
    </div>

    <details class="card advanced-card">
        <summary><span><b>高级配置 / 旧模式保留</b><small>平时不用改，只有你要重新接自动上游或统一改价时再打开。</small></span><em>展开</em></summary>
        <div class="advanced-body">
            <div class="section-title"><h2>上游模式</h2><span class="badge">保持本地库存即可</span></div>
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

            <div class="section-title advanced-section"><h2>统一价格规则</h2><span class="badge">默认全站生效</span></div>
            <p class="help">售价公式：<code>max(成本USD × 汇率 × 加价倍数 + 固定手续费, 成本CNY + 最低利润, 最低售价)</code>。本地库存模式下通常直接在“商品管理”里改每个商品售价即可。</p>
            <div class="grid3">
                <div class="form-row"><label>USD/CNY 汇率</label><input name="pricing_exchange_rate" value="{{ $values['pricing_exchange_rate'] }}" placeholder="7.3"></div>
                <div class="form-row"><label>统一加价倍数</label><input name="pricing_markup_multiplier" value="{{ $values['pricing_markup_multiplier'] }}" placeholder="1"></div>
                <div class="form-row"><label>统一固定手续费</label><input name="pricing_fixed_fee" value="{{ $values['pricing_fixed_fee'] }}" placeholder="0"></div>
                <div class="form-row"><label>统一最低利润</label><input name="pricing_min_profit" value="{{ $values['pricing_min_profit'] }}" placeholder="1"></div>
                <div class="form-row"><label>统一最低售价</label><input name="pricing_min_price" value="{{ $values['pricing_min_price'] }}" placeholder="3"></div>
            </div>
        </div>
    </details>

    <div class="top-actions settings-actions"><button type="submit">保存配置</button><a class="btn secondary" href="{{ route('sms.admin.inventory') }}">去导入库存</a><a class="btn secondary" href="{{ route('sms.admin.prices') }}">去商品设置</a></div>
</form>
@endsection
