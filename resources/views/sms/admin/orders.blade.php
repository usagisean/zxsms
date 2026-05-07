@extends('sms.admin.layout')
@section('title','接码订单')
@section('subtitle','查看接码订单、HeroSMS activation、支付状态和验证码结果。')
@section('content')
<div class="card">
    <form class="grid3">
        <div class="form-row"><label>搜索</label><input name="q" value="{{ request('q') }}" placeholder="订单 / 号码 / activation"></div>
        <div class="form-row"><label>状态</label><input name="status" value="{{ request('status') }}" placeholder="waiting_code / completed"></div>
        <div style="align-self:end"><button>筛选</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>订单</th><th>服务/国家</th><th>价格</th><th>支付</th><th>号码/验证码</th><th>状态</th><th>时间</th></tr></thead>
        <tbody>@forelse($orders as $o)<tr>
            <td class="mono"><a href="{{ route('sms.order.show',$o->token) }}">{{ $o->order_sn }}</a><br><span class="muted">{{ $o->provider_activation_id ?: '-' }}</span></td>
            <td><b>{{ $o->service->name ?? $o->service_code }}</b><br><span class="muted">{{ $o->country->name ?? $o->country_code }}</span></td>
            <td>成本 ${{ $o->cost_usd }}<br><b>售价 ¥{{ $o->sale_price }}</b></td>
            <td>{{ optional($o->latestPayment)->method_code ?: ($o->wallet_paid_at ? 'balance' : '-') }}<br><span class="muted">{{ optional($o->latestPayment)->status ?: ($o->wallet_paid_at ? 'paid' : '-') }}</span></td>
            <td class="mono">{{ $o->phone_number ?: '-' }}<br>{{ $o->sms_code ?: '-' }}</td>
            <td><span class="badge">{{ $o->status }}</span>@if($o->status_note)<br><span class="danger small">{{ $o->status_note }}</span>@endif</td>
            <td>{{ $o->created_at }}<br><span class="muted">{{ optional($o->paid_at)->toDateTimeString() }}</span></td>
        </tr>@empty<tr><td colspan="7" class="empty">暂无接码订单。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $orders->links() }}
</div>
@endsection
