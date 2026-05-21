@extends('sms.admin.layout')
@section('title','号码库存')
@section('subtitle','把上游买到的长效号码导入这里。默认有效期按系统配置生成，真实渠道和取码链接不会暴露给用户。')
@section('actions')
<form method="post" action="{{ route('sms.admin.inventory.sync') }}">@csrf<button class="btn secondary" type="submit">刷新前台库存价格</button></form>
@endsection
@section('content')
<div class="stat-grid">
    <div class="stat"><span>可售库存</span><b>{{ $stats['available'] }}</b></div>
    <div class="stat"><span>已发货</span><b>{{ $stats['sold'] }}</b></div>
    <div class="stat"><span>已过期</span><b>{{ $stats['expired'] }}</b></div>
    <div class="stat"><span>已禁用</span><b>{{ $stats['disabled'] }}</b></div>
</div>

<div class="card">
    <div class="section-title"><h2>批量导入</h2><span class="badge">默认 {{ $defaultValidityDays ?? 60 }} 天有效 · 最低 {{ $minValidityDays ?? 30 }} 天</span></div>
    <p class="help">最简单格式每行一条：<code>+13505009864|http://a.62-us.com/api/get_sms?key=xxx</code>。下面的默认平台、售价、有效期会套用到每一行。有效期默认来自“系统配置 → 长效接码商品设置”；低于最短有效天数的库存会跳过且不会上架。也兼容高级格式：<code>paypal|PayPal|+手机号|取码URL|售价|有效期</code>。</p>
    <form method="post" action="{{ route('sms.admin.inventory.import') }}">@csrf
        <div class="grid3">
            <div class="form-row"><label>平台 code</label><input name="service_code" value="paypal" placeholder="paypal / telegram / qq"></div>
            <div class="form-row"><label>平台名称</label><input name="service_name" value="PayPal" placeholder="PayPal"></div>
            <div class="form-row"><label>国家 ID / 名称</label><div class="grid" style="gap:8px"><input name="country_code" value="1"><input name="country_name" value="美国"></div></div>
            <div class="form-row"><label>成本 CNY（内部）</label><input name="cost_cny" value="2.00"></div>
            <div class="form-row"><label>前台售价 CNY</label><input name="sale_price" value="5.99"></div>
            <div class="form-row"><label>有效期</label><input name="valid_until" value="{{ $defaultValidUntil ?? now()->addDays(60)->toDateString() }}" placeholder="2026-07-21"></div>
        </div>
        <div class="form-row" style="margin-top:14px"><label>手机号与取码链接</label><textarea name="lines" rows="8" placeholder="+13505009864|http://a.62-us.com/api/get_sms?key=xxx"></textarea></div>
        <div class="top-actions" style="justify-content:flex-start;margin-top:14px"><button type="submit">导入并上架</button></div>
    </form>
</div>

<div class="card">
    <div class="section-title"><h2>库存列表</h2><span class="badge">取码链接已隐藏</span></div>
    <form class="grid3" style="margin-bottom:14px">
        <div class="form-row"><label>搜索</label><input name="q" value="{{ request('q') }}" placeholder="平台 / 手机号 / 库存编号"></div>
        <div class="form-row"><label>状态</label><input name="status" value="{{ request('status') }}" placeholder="available / sold / expired"></div>
        <div style="align-self:end"><button>筛选</button></div>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>库存编号</th><th>平台</th><th>号码</th><th>价格</th><th>状态</th><th>归属订单/用户</th><th>有效期/时间</th></tr></thead>
        <tbody>@forelse($cards as $card)<tr>
            <td class="mono">{{ $card->cdk_code }}</td>
            <td><b>{{ $card->service_name }}</b><br><span class="muted mono">{{ $card->service_code }} / {{ $card->country_name }}</span></td>
            <td class="mono">{{ $card->phone_number }}</td>
            <td>成本 ¥{{ $card->cost_cny }}<br><b>售价 ¥{{ $card->sale_price }}</b></td>
            <td><span class="badge">{{ $card->status }}</span>@if($card->sms_code)<br><span class="ok" style="display:inline-flex;width:auto;margin:8px 0 0;padding:5px 8px">已收到 {{ $card->sms_code }}</span>@endif</td>
            <td>@if($card->order)<a class="mono" href="{{ route('sms.order.show',$card->order->token) }}">{{ $card->order->order_sn }}</a>@else - @endif<br><span class="muted">{{ optional($card->user)->email ?: '-' }}</span></td>
            <td>{{ optional($card->valid_until)->toDateString() ?: '长期' }}<br><span class="muted">{{ optional($card->sold_at)->toDateTimeString() ?: $card->created_at }}</span></td>
        </tr>@empty<tr><td colspan="7" class="empty">暂无库存。导入后会自动出现在前台商品列表。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $cards->links() }}
</div>
@endsection
