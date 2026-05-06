@extends('sms.layouts.site')
@section('title', '余额充值 - ZXAIHUB SMS')
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="eyebrow">💳 账户充值</div>
            <h1 style="font-size:clamp(36px,5vw,66px);margin:0 0 14px">先充值余额，再自动接码</h1>
            <p class="muted" style="font-size:18px;line-height:1.8">正式模式下，购买号码从账户余额扣款；20 分钟内未收到验证码、HeroSMS 无库存或取号失败，会自动退回到你的余额。</p>
            <div class="grid3" style="margin-top:22px">
                <div class="stat"><b>¥{{ number_format((float)$wallet->balance, 2) }}</b><span>当前余额</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_recharged, 2) }}</b><span>累计充值</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_refunded, 2) }}</b><span>累计退回</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <form method="post" action="{{ route('sms.recharge.create') }}" class="panel panel-black">
            @csrf
            <h2 style="margin-top:0;font-size:30px">选择充值档位</h2>
            @if($plans->isEmpty())
                <div class="empty">暂无启用充值档位，请到后台配置。</div>
            @else
                <div class="grid3">
                    @foreach($plans as $plan)
                        <label class="pay-card" style="align-items:flex-start;min-height:132px">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" @if($loop->first) checked @endif>
                            <span style="width:100%">
                                <b style="font-size:24px">¥{{ number_format((float)$plan->amount, 2) }}</b>
                                <span class="dim" style="display:block;margin-top:6px">{{ $plan->name }}</span>
                                @if((float)$plan->bonus_amount > 0)<span class="pill" style="margin-top:10px">赠送 ¥{{ number_format((float)$plan->bonus_amount,2) }}</span>@endif
                                @if($plan->badge)<span class="pill" style="margin-top:10px">{{ $plan->badge }}</span>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            <h3 style="font-size:22px;margin:26px 0 14px">支付方式</h3>
            @if(empty($methods))
                <div class="err" style="width:100%;margin:0">暂无启用支付方式，请先在后台或 .env 配置易支付 / USDT。</div>
            @else
                <div class="service-grid">
                    @foreach($methods as $code => $method)
                        <label class="pay-card">
                            <input type="radio" name="payment_method" value="{{ $code }}" @if($loop->first) checked @endif>
                            <span><b>{{ $method['name'] }}</b><br><span class="dim">{{ $method['driver'] }}</span></span>
                        </label>
                    @endforeach
                </div>
            @endif
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:24px" @if($plans->isEmpty() || empty($methods)) disabled @endif>创建充值订单</button>
        </form>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">最近充值</h2>
            @if($orders->isEmpty())
                <div class="empty">暂无充值记录。</div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>充值单</th><th>支付金额</th><th>到账金额</th><th>方式</th><th>状态</th><th>时间</th><th>操作</th></tr></thead>
                    <tbody>@foreach($orders as $order)<tr>
                        <td class="mono">{{ $order->recharge_sn }}</td>
                        <td>¥{{ number_format((float)$order->amount,2) }}</td>
                        <td>¥{{ number_format((float)$order->total_amount,2) }}</td>
                        <td>{{ $order->method_code }}</td>
                        <td><span class="status">{{ $order->status }}</span></td>
                        <td>{{ $order->created_at }}</td>
                        <td><a class="btn btn-dark" href="{{ route('sms.recharge.show', $order->token) }}">查看</a></td>
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </div>
    </div>
</section>
@endsection
