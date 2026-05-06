@extends('sms.layouts.site')
@section('title', '订单查询 - ZXAIHUB SMS')
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="eyebrow">🔎 游客订单查询</div>
            <h1 style="font-size:clamp(34px,5vw,62px);margin:0 0 14px">输入订单号或邮箱，找回你的号码</h1>
            <p class="muted" style="font-size:18px;line-height:1.8">注册用户也可以直接进入“我的号码”。游客下单如果设置了查询密码，需要同时输入查询密码。</p>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <form class="panel panel-black" method="post" action="{{ route('sms.query.post') }}">
            @csrf
            <div class="grid3">
                <div class="field"><label>订单号</label><input name="order_sn" value="{{ old('order_sn') }}" placeholder="SMS2026..."></div>
                <div class="field"><label>邮箱</label><input type="email" name="email" value="{{ old('email') }}" placeholder="或使用邮箱查询最近订单"></div>
                <div class="field"><label>查询密码</label><input name="query_password" value="{{ old('query_password') }}" placeholder="如果下单时设置了密码"></div>
            </div>
            <button class="btn btn-primary" type="submit">查询订单</button>
        </form>
    </div>
</section>

@if($orders !== null)
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">查询结果</h2>
            @if($orders->isEmpty())
                <div class="empty">没有找到订单，或查询密码不正确。</div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>订单号</th><th>服务</th><th>国家</th><th>状态</th><th>号码</th><th>验证码</th><th>操作</th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td class="mono">{{ $order->order_sn }}</td>
                            <td>{{ $order->service->name ?? $order->service_code }}</td>
                            <td>{{ $order->country->name ?? $order->country_code }}</td>
                            <td><span class="status">{{ $order->status }}</span></td>
                            <td class="mono">{{ $order->phone_number ?: '-' }}</td>
                            <td class="mono">{{ $order->sms_code ?: '-' }}</td>
                            <td><a class="btn btn-dark" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">查看</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table></div>
            @endif
        </div>
    </div>
</section>
@endif
@endsection
