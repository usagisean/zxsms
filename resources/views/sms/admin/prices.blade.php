@extends('sms.admin.layout')
@section('title','价格')
@section('content')
<div class="card"><h1>价格同步</h1><form method="post" action="{{ route('sms.admin.prices.sync') }}" class="grid3">@csrf<div><label>服务 code（可空）</label><input name="service" value="{{ request('service') }}"></div><div><label>国家 id（可空）</label><input name="country" value="{{ request('country') }}"></div><div style="align-self:end"><button>同步 HeroSMS</button></div></form></div>
<div class="card"><table class="table"><thead><tr><th>服务</th><th>国家</th><th>成本USD</th><th>售价CNY</th><th>库存</th><th>状态</th><th>同步时间</th></tr></thead><tbody>@foreach($prices as $p)<tr><td>{{ $p->service->name ?? '-' }}<br><span class="mono">{{ $p->provider_service_code }}</span></td><td>{{ $p->country->name ?? '-' }}<br>{{ $p->provider_country_id }}</td><td>{{ $p->cost_usd }}</td><td>¥{{ $p->sale_price }}</td><td>{{ $p->stock_count }}</td><td>{{ $p->is_available ? '显示':'隐藏' }}</td><td>{{ optional($p->synced_at)->toDateTimeString() }}</td></tr>@endforeach</tbody></table><div>{{ $prices->links() }}</div></div>
@endsection
