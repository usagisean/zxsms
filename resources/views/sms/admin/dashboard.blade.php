@extends('sms.admin.layout')
@section('title','运营看板')
@section('subtitle','围绕“导入长效号码库存 → 上架商品 → 用户充值购买 → 站内查码”的业务做最少但够用的管理。')
@section('actions')<a class="btn secondary" href="{{ route('sms.index') }}" target="_blank">打开前台</a><a class="btn secondary" href="{{ route('sms.admin.inventory') }}">导入号码</a><a class="btn" href="{{ route('sms.admin.prices') }}">商品设置</a>@endsection
@section('content')
<div class="stat-grid">
    <div class="stat"><span>可售号码库存</span><b>{{ $counts['available_inventory'] }}</b><small>可直接发货</small></div>
    <div class="stat"><span>已发货号码</span><b>{{ $counts['sold_inventory'] }}</b><small>累计售出库存</small></div>
    <div class="stat"><span>前台商品</span><b>{{ $counts['visible_products'] }}</b><small>正在展示</small></div>
    <div class="stat"><span>等待验证码</span><b>{{ $counts['waiting_code'] }}</b><small>用户正在接码</small></div>
    <div class="stat"><span>今日订单</span><b>{{ $counts['today_orders'] }}</b><small>接码购买单</small></div>
    <div class="stat"><span>今日余额消费</span><b>¥{{ number_format(abs($counts['today_sales']), 2) }}</b><small>余额扣款合计</small></div>
    <div class="stat"><span>今日充值</span><b>¥{{ number_format($counts['today_recharge'], 2) }}</b><small>已支付充值</small></div>
    <div class="stat"><span>注册用户</span><b>{{ $counts['registered_users'] }}</b><small>站内账号</small></div>
</div>

@if($counts['refund_required'] > 0 || $counts['low_stock_products'] > 0)
<div class="notice-grid">
    @if($counts['refund_required'] > 0)
        <a class="notice-card warn" href="{{ route('sms.admin.orders') }}">
            <strong>{{ $counts['refund_required'] }} 个订单需要处理</strong>
            <span>可能是取码失败或异常状态，建议优先查看订单并补发/退款。</span>
        </a>
    @endif
    @if($counts['low_stock_products'] > 0)
        <a class="notice-card" href="{{ route('sms.admin.prices') }}">
            <strong>{{ $counts['low_stock_products'] }} 个商品库存偏低</strong>
            <span>库存 ≤ 2，建议导入新号码或暂时隐藏商品。</span>
        </a>
    @endif
</div>
@endif

<div class="workflow-grid">
    <div class="card">
        <span class="badge">Step 1</span>
        <h2>导入长效号码</h2>
        <p class="help">把上游买到的 <code>手机号|取码链接</code> 批量导入本地库存。真实取码链接只保存在后台，前台不会暴露渠道。</p>
        <a class="btn secondary" href="{{ route('sms.admin.inventory') }}">去导入库存</a>
    </div>
    <div class="card">
        <span class="badge">Step 2</span>
        <h2>配置前台商品</h2>
        <p class="help">设置每个服务的展示名、售价、库存数量和是否上架。用户只能购买你允许展示的商品。</p>
        <a class="btn secondary" href="{{ route('sms.admin.prices') }}">去商品设置</a>
    </div>
    <div class="card">
        <span class="badge">Step 3</span>
        <h2>用户充值购买</h2>
        <p class="help">用户注册登录后充值余额，用余额下单。库存不足、发货失败或未发货取消都会退回余额。</p>
        <a class="btn secondary" href="{{ route('sms.admin.recharges') }}">看充值记录</a>
    </div>
    <div class="card">
        <span class="badge">Step 4</span>
        <h2>站内查码留痕</h2>
        <p class="help">购买成功后订单保存在“我的号码”，也可以按邮箱/订单号找回，方便你售后和对账。</p>
        <a class="btn secondary" href="{{ route('sms.admin.orders') }}">看接码订单</a>
    </div>
</div>

<div class="card">
    <div class="section-title"><h2>现在最常用的后台入口</h2><span class="badge">简化版</span></div>
    <div class="quick-grid">
        <a href="{{ route('sms.admin.inventory') }}"><b>号码库存</b><span>导入、查看、同步可售库存</span></a>
        <a href="{{ route('sms.admin.prices') }}"><b>商品管理</b><span>上架/隐藏、改价、限制购买数量</span></a>
        <a href="{{ route('sms.admin.orders') }}"><b>订单管理</b><span>查号码、查验证码、处理异常单</span></a>
        <a href="{{ route('sms.admin.settings') }}"><b>站点配置</b><span>品牌、TG、支付和长效号规则</span></a>
    </div>
</div>
@endsection
