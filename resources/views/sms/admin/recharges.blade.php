@extends('sms.admin.layout')
@section('title','充值订单')
@section('content')
<div class="card"><h1>充值订单</h1><form class="grid3"><input name="q" value="{{ request('q') }}" placeholder="充值单/支付单/交易号"><input name="status" value="{{ request('status') }}" placeholder="状态"><button>筛选</button></form></div>
<div class="card"><table class="table"><thead><tr><th>充值单</th><th>用户</th><th>金额</th><th>支付</th><th>状态</th><th>时间</th></tr></thead><tbody>@foreach($recharges as $r)<tr><td class="mono">{{ $r->recharge_sn }}<br>{{ $r->payment_sn }}</td><td>{{ optional($r->user)->email }}</td><td>支付 ¥{{ $r->amount }}<br>到账 ¥{{ $r->total_amount }} @if((float)$r->bonus_amount>0)<br>赠送 ¥{{ $r->bonus_amount }}@endif</td><td>{{ $r->method_code }}<br>{{ $r->trade_no }}</td><td>{{ $r->status }}</td><td>{{ $r->created_at }}<br>{{ optional($r->paid_at)->toDateTimeString() }}</td></tr>@endforeach</tbody></table><div>{{ $recharges->links() }}</div></div>
@endsection
