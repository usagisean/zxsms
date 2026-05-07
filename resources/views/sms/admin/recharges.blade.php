@extends('sms.admin.layout')
@section('title','充值订单')
@section('subtitle','查看用户充值、支付单和入账状态。')
@section('content')
<div class="card">
    <form class="grid3">
        <div class="form-row"><label>搜索</label><input name="q" value="{{ request('q') }}" placeholder="充值单 / 支付单 / 交易号"></div>
        <div class="form-row"><label>状态</label><input name="status" value="{{ request('status') }}" placeholder="pending / paid / expired"></div>
        <div style="align-self:end"><button>筛选</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>充值单</th><th>用户</th><th>金额</th><th>支付</th><th>状态</th><th>时间</th></tr></thead>
        <tbody>@forelse($recharges as $r)<tr>
            <td class="mono"><b>{{ $r->recharge_sn }}</b><br><span class="muted">{{ $r->payment_sn }}</span></td>
            <td>{{ optional($r->user)->email ?: '-' }}</td>
            <td>支付 ¥{{ $r->amount }}<br>到账 ¥{{ $r->total_amount }} @if((float)$r->bonus_amount>0)<br><span class="badge">赠送 ¥{{ $r->bonus_amount }}</span>@endif</td>
            <td>{{ $r->method_code }}<br><span class="mono muted">{{ $r->trade_no ?: '-' }}</span></td>
            <td><span class="badge">{{ $r->status }}</span></td>
            <td>{{ $r->created_at }}<br><span class="muted">{{ optional($r->paid_at)->toDateTimeString() }}</span></td>
        </tr>@empty<tr><td colspan="6" class="empty">暂无充值订单。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $recharges->links() }}
</div>
@endsection
