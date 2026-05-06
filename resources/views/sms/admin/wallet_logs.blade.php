@extends('sms.admin.layout')
@section('title','余额流水')
@section('content')
<div class="card"><h1>余额流水</h1><form class="grid3"><input name="q" value="{{ request('q') }}" placeholder="用户邮箱"><input name="type" value="{{ request('type') }}" placeholder="recharge/spend/refund"><button>筛选</button></form></div>
<div class="card"><table class="table"><thead><tr><th>时间</th><th>用户</th><th>类型</th><th>金额</th><th>余额变化</th><th>关联</th><th>备注</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ optional($log->user)->email }}</td><td>{{ $log->type }}</td><td class="mono">{{ (float)$log->amount >= 0 ? '+' : '' }}{{ $log->amount }}</td><td>¥{{ $log->balance_before }} → ¥{{ $log->balance_after }}</td><td class="mono">@if($log->order){{ $log->order->order_sn }}@endif @if($log->rechargeOrder)<br>{{ $log->rechargeOrder->recharge_sn }}@endif</td><td>{{ $log->remark }}</td></tr>@endforeach</tbody></table><div>{{ $logs->links() }}</div></div>
@endsection
