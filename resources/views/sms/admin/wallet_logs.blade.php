@extends('sms.admin.layout')
@section('title','余额流水')
@section('subtitle','追踪用户充值、消费和自动退款流水。')
@section('content')
<div class="card">
    <form class="grid3">
        <div class="form-row"><label>用户邮箱</label><input name="q" value="{{ request('q') }}" placeholder="user@example.com"></div>
        <div class="form-row"><label>类型</label><input name="type" value="{{ request('type') }}" placeholder="recharge / spend / refund"></div>
        <div style="align-self:end"><button>筛选</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>时间</th><th>用户</th><th>类型</th><th>金额</th><th>余额变化</th><th>关联</th><th>备注</th></tr></thead>
        <tbody>@forelse($logs as $log)<tr>
            <td>{{ $log->created_at }}</td><td>{{ optional($log->user)->email ?: '-' }}</td><td><span class="badge">{{ $log->type }}</span></td>
            <td class="mono">{{ (float)$log->amount >= 0 ? '+' : '' }}{{ $log->amount }}</td><td>¥{{ $log->balance_before }} → ¥{{ $log->balance_after }}</td>
            <td class="mono">@if($log->order){{ $log->order->order_sn }}@endif @if($log->rechargeOrder)<br>{{ $log->rechargeOrder->recharge_sn }}@endif</td><td>{{ $log->remark }}</td>
        </tr>@empty<tr><td colspan="7" class="empty">暂无余额流水。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $logs->links() }}
</div>
@endsection
