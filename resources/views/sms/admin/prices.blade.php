@extends('sms.admin.layout')
@section('title','价格')
@section('subtitle','前台展示缓存价格；库存模式下来自“号码库存”的可售数量和售价。')
@section('actions')<a class="btn secondary" href="{{ route('sms.admin.settings') }}">统一定价</a>@endsection
@section('content')
<div class="card">
    <div class="section-title"><h2>价格同步</h2><span class="badge">库存 / Provider</span></div>
    <form method="post" action="{{ route('sms.admin.prices.sync') }}" class="grid3">@csrf
        <div class="form-row"><label>服务 code（可空）</label><input name="service" value="{{ request('service') }}" placeholder="如 go / tg"></div>
        <div class="form-row"><label>国家 id（可空）</label><input name="country" value="{{ request('country') }}" placeholder="如 0"></div>
        <div style="align-self:end"><button>同步价格</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>服务</th><th>国家</th><th>成本USD</th><th>售价CNY</th><th>库存</th><th>状态</th><th>同步时间</th></tr></thead>
        <tbody>@forelse($prices as $p)<tr>
            <td><b>{{ $p->service->name ?? '-' }}</b><br><span class="mono muted">{{ $p->provider_service_code }}</span></td>
            <td>{{ $p->country->name ?? '-' }}<br><span class="mono muted">{{ $p->provider_country_id }}</span></td>
            <td class="mono">${{ $p->cost_usd }}</td><td><b>¥{{ $p->sale_price }}</b></td><td>{{ $p->stock_count }}</td><td><span class="badge">{{ $p->is_available ? '显示':'隐藏' }}</span></td><td>{{ optional($p->synced_at)->toDateTimeString() }}</td>
        </tr>@empty<tr><td colspan="7" class="empty">暂无价格，请先导入号码库存并同步价格。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $prices->links() }}
</div>
@endsection
