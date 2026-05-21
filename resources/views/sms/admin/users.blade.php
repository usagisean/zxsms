@extends('sms.admin.layout')
@section('title','用户管理')
@section('subtitle','查看注册用户、账户余额、充值次数和接码订单统计。')
@section('content')
<div class="card">
    <form class="grid3">
        <div class="form-row"><label>搜索用户</label><input name="q" value="{{ request('q') }}" placeholder="邮箱 / 昵称"></div>
        <div></div>
        <div style="align-self:end"><button>筛选</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th>用户</th><th>余额</th><th>累计</th><th>订单统计</th><th>注册时间</th><th>操作</th></tr></thead>
        <tbody>@forelse($users as $user)
            @php($wallet = $user->smsWallet)
            <tr>
                <td><b>{{ $user->name ?: '未设置昵称' }}</b><br><span class="mono muted">{{ $user->email }}</span></td>
                <td><b>¥{{ number_format((float) optional($wallet)->balance, 2) }}</b></td>
                <td>充值 ¥{{ number_format((float) optional($wallet)->total_recharged, 2) }}<br>消费 ¥{{ number_format((float) optional($wallet)->total_spent, 2) }}<br>退款 ¥{{ number_format((float) optional($wallet)->total_refunded, 2) }}</td>
                <td><span class="badge">接码 {{ $user->sms_orders_count }}</span><br><span class="badge" style="margin-top:6px">充值 {{ $user->sms_recharge_orders_count }}</span></td>
                <td>{{ $user->created_at }}<br><span class="muted">{{ optional($user->email_verified_at)->toDateTimeString() ?: '未验证邮箱' }}</span></td>
                <td><a class="btn small secondary" href="{{ route('sms.admin.orders', ['q' => $user->email]) }}">查接码订单</a><br><a class="btn small secondary" style="margin-top:8px" href="{{ route('sms.admin.wallet-logs', ['q' => $user->email]) }}">查余额流水</a></td>
            </tr>
        @empty<tr><td colspan="6" class="empty">暂无注册用户。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $users->links() }}
</div>
@endsection
