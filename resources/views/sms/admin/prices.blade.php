@extends('sms.admin.layout')
@section('title','价格')
@section('subtitle','前台展示缓存价格；用户下单前仍会实时确认 HeroSMS 成本，防止亏本。')
@section('actions')<a class="btn secondary" href="{{ route('sms.admin.settings') }}">统一定价</a>@endsection
@section('content')
<div class="card">
    <div class="section-title"><h2>价格同步</h2><span class="badge">HeroSMS</span></div>
    <form method="post" action="{{ route('sms.admin.prices.sync') }}" class="grid3">@csrf
        <div class="form-row"><label>服务 code（可空）</label><input name="service" value="{{ request('service') }}" placeholder="如 go / tg"></div>
        <div class="form-row"><label>国家 id（可空）</label><input name="country" value="{{ request('country') }}" placeholder="如 0"></div>
        <div style="align-self:end"><button>同步 HeroSMS</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>服务</th><th>国家</th><th>成本USD</th><th>售价CNY</th><th>库存</th><th>状态</th><th>同步时间</th></tr></thead>
        <tbody>@forelse($prices as $p)<tr>
            <td><b>{{ $p->service->name ?? '-' }}</b><br><span class="mono muted">{{ $p->provider_service_code }}</span></td>
            <td>{{ $p->country->name ?? '-' }}<br><span class="mono muted">{{ $p->provider_country_id }}</span></td>
            <td class="mono">${{ $p->cost_usd }}</td><td><b>¥{{ $p->sale_price }}</b></td><td>{{ $p->stock_count }}</td><td><span class="badge">{{ $p->is_available ? '显示':'隐藏' }}</span></td><td>{{ optional($p->synced_at)->toDateTimeString() }}</td>
        </tr>@empty<tr><td colspan="7" class="empty">暂无价格，请先同步 HeroSMS。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $prices->links() }}
</div>
@endsection
