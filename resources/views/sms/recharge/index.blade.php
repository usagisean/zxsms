@extends('sms.layouts.site')
@section('title', __('sms.recharge.title'))
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="eyebrow">{{ __('sms.recharge.eyebrow') }}</div>
            <h1 style="font-size:clamp(36px,5vw,66px);margin:0 0 14px">{{ __('sms.recharge.headline') }}</h1>
            <p class="muted" style="font-size:18px;line-height:1.8">{{ __('sms.recharge.sub') }}</p>
            <div class="grid3" style="margin-top:22px">
                <div class="stat"><b>¥{{ number_format((float)$wallet->balance, 2) }}</b><span>{{ __('sms.recharge.current_balance') }}</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_recharged, 2) }}</b><span>{{ __('sms.recharge.total_recharged') }}</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_refunded, 2) }}</b><span>{{ __('sms.recharge.total_refunded') }}</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <form method="post" action="{{ route('sms.recharge.create') }}" class="panel panel-black">
            @csrf
            <h2 style="margin-top:0;font-size:30px">{{ __('sms.recharge.choose_plan') }}</h2>
            @if($plans->isEmpty())
                <div class="empty">{{ __('sms.recharge.no_plans') }}</div>
            @else
                <div class="grid3">
                    @foreach($plans as $plan)
                        <label class="pay-card" style="align-items:flex-start;min-height:132px">
                            <input type="radio" name="plan_id" value="{{ $plan->id }}" @if($loop->first) checked @endif>
                            <span style="width:100%">
                                <b style="font-size:24px">¥{{ number_format((float)$plan->amount, 2) }}</b>
                                <span class="dim" style="display:block;margin-top:6px">{{ $plan->name }}</span>
                                @if((float)$plan->bonus_amount > 0)<span class="pill" style="margin-top:10px">{{ __('sms.recharge.bonus', ['amount'=>number_format((float)$plan->bonus_amount,2)]) }}</span>@endif
                                @if($plan->badge)<span class="pill" style="margin-top:10px">{{ $plan->badge }}</span>@endif
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif

            <h3 style="font-size:22px;margin:26px 0 14px">{{ __('sms.sms.payment_method') }}</h3>
            @if(empty($methods))
                <div class="err" style="width:100%;margin:0">{{ __('sms.recharge.no_methods') }}</div>
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
            <button class="btn btn-primary btn-block" type="submit" style="margin-top:24px" @if($plans->isEmpty() || empty($methods)) disabled @endif>{{ __('sms.recharge.create_order') }}</button>
        </form>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">{{ __('sms.recharge.recent') }}</h2>
            @if($orders->isEmpty())
                <div class="empty">{{ __('sms.recharge.no_records') }}</div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>{{ __('sms.recharge.recharge_order') }}</th><th>{{ __('sms.recharge.pay_amount') }}</th><th>{{ __('sms.recharge.credit_amount') }}</th><th>{{ __('sms.common.method') }}</th><th>{{ __('sms.common.status') }}</th><th>{{ __('sms.common.time') }}</th><th>{{ __('sms.common.action') }}</th></tr></thead>
                    <tbody>@foreach($orders as $order)<tr>
                        <td class="mono">{{ $order->recharge_sn }}</td>
                        <td>¥{{ number_format((float)$order->amount,2) }}</td>
                        <td>¥{{ number_format((float)$order->total_amount,2) }}</td>
                        <td>{{ $order->method_code }}</td>
                        <td><span class="status">{{ __('sms.status.' . $order->status) === 'sms.status.' . $order->status ? $order->status : __('sms.status.' . $order->status) }}</span></td>
                        <td>{{ $order->created_at }}</td>
                        <td><a class="btn btn-dark" href="{{ route('sms.recharge.show', $order->token) }}">{{ __('sms.common.view') }}</a></td>
                    </tr>@endforeach</tbody>
                </table></div>
            @endif
        </div>
    </div>
</section>
@endsection
