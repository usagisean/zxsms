@extends('sms.layouts.site')
@section('title', '我的号码 - ZXAIHUB SMS')
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="eyebrow">📱 我的号码</div>
            <h1 style="font-size:clamp(34px,5vw,62px);margin:0 0 14px">{{ auth()->user()->name ?: auth()->user()->email }} 的账户中心</h1>
            <p class="muted" style="font-size:18px;line-height:1.8">这里集中展示余额、充值记录、余额流水和接码订单。游客历史订单请使用“订单查询”。</p>
            <div class="grid3" style="margin-top:22px">
                <div class="stat"><b>¥{{ number_format((float)$wallet->balance,2) }}</b><span>当前余额</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_spent,2) }}</b><span>累计接码扣款</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_refunded,2) }}</b><span>累计自动退回</span></div>
            </div>
            <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
                <a class="btn btn-primary" href="{{ route('sms.index') }}">获取新号码</a>
                <a class="btn btn-white" href="{{ route('sms.recharge.index') }}">充值余额</a>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container grid">
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:28px">最近充值</h2>
            @if($recharges->isEmpty())
                <div class="empty">暂无充值记录。</div>
            @else
                <div class="table-wrap"><table class="table" style="min-width:520px"><thead><tr><th>单号</th><th>到账</th><th>状态</th><th>操作</th></tr></thead><tbody>
                    @foreach($recharges as $r)<tr><td class="mono">{{ $r->recharge_sn }}</td><td>¥{{ number_format((float)$r->total_amount,2) }}</td><td><span class="status">{{ $r->status }}</span></td><td><a href="{{ route('sms.recharge.show',$r->token) }}">查看</a></td></tr>@endforeach
                </tbody></table></div>
            @endif
        </div>
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:28px">余额流水</h2>
            @if($logs->isEmpty())
                <div class="empty">暂无余额流水。</div>
            @else
                <div class="table-wrap"><table class="table" style="min-width:560px"><thead><tr><th>类型</th><th>金额</th><th>余额</th><th>备注</th></tr></thead><tbody>
                    @foreach($logs as $log)<tr><td>{{ $log->type }}</td><td class="mono">{{ (float)$log->amount >= 0 ? '+' : '' }}{{ number_format((float)$log->amount,2) }}</td><td>¥{{ number_format((float)$log->balance_after,2) }}</td><td>{{ $log->remark }}<br><span class="dim">{{ $log->created_at }}</span></td></tr>@endforeach
                </tbody></table></div>
            @endif
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">接码订单</h2>
            @if($orders->isEmpty())
                <div class="empty">还没有订单。现在去获取第一个号码吧。</div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>订单号</th><th>服务/国家</th><th>价格</th><th>状态</th><th>号码</th><th>验证码</th><th>退款</th><th>操作</th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td class="mono">{{ $order->order_sn }}</td>
                            <td>{{ $order->service->name ?? $order->service_code }}<br><span class="dim">{{ $order->country->name ?? $order->country_code }}</span></td>
                            <td>¥{{ number_format((float)$order->sale_price,2) }}</td>
                            <td><span class="status">{{ $order->status }}</span></td>
                            <td class="mono">{{ $order->phone_number ?: '-' }}</td>
                            <td class="mono">{{ $order->sms_code ?: '-' }}</td>
                            <td>{{ $order->wallet_refunded_at ? '已退 ¥'.number_format((float)$order->wallet_amount,2) : '-' }}</td>
                            <td><a class="btn btn-dark" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">查看</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
                <div style="margin-top:18px">{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</section>
@endsection
