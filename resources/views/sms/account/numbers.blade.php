@extends('sms.layouts.site')
@section('title', __('sms.account.title'))
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="eyebrow">{{ __('sms.account.eyebrow') }}</div>
            <h1 style="font-size:clamp(34px,5vw,62px);margin:0 0 14px">{{ __('sms.account.headline', ['name' => auth()->user()->name ?: auth()->user()->email]) }}</h1>
            <p class="muted" style="font-size:18px;line-height:1.8">{{ __('sms.account.sub') }}</p>
            <div class="grid3" style="margin-top:22px">
                <div class="stat"><b>¥{{ number_format((float)$wallet->balance,2) }}</b><span>{{ __('sms.recharge.current_balance') }}</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_spent,2) }}</b><span>{{ __('sms.account.spent') }}</span></div>
                <div class="stat"><b>¥{{ number_format((float)$wallet->total_refunded,2) }}</b><span>{{ __('sms.account.refunded') }}</span></div>
            </div>
            <div style="margin-top:24px;display:flex;gap:12px;flex-wrap:wrap">
                <a class="btn btn-primary" href="{{ route('sms.index') }}">{{ __('sms.account.new_number') }}</a>
                <a class="btn btn-white" href="{{ route('sms.recharge.index') }}">{{ __('sms.nav.recharge') }}</a>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container grid">
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:28px">{{ __('sms.account.recent_recharge') }}</h2>
            @if($recharges->isEmpty())
                <div class="empty">{{ __('sms.recharge.no_records') }}</div>
            @else
                <div class="table-wrap"><table class="table" style="min-width:520px"><thead><tr><th>{{ __('sms.common.order_no') }}</th><th>{{ __('sms.recharge.credit_amount') }}</th><th>{{ __('sms.common.status') }}</th><th>{{ __('sms.common.action') }}</th></tr></thead><tbody>
                    @foreach($recharges as $r)
                        @php
                            $statusKey = 'sms.status.' . $r->status;
                            $statusText = __($statusKey);
                            if ($statusText === $statusKey) { $statusText = $r->status; }
                        @endphp
                        <tr><td class="mono">{{ $r->recharge_sn }}</td><td>¥{{ number_format((float)$r->total_amount,2) }}</td><td><span class="status">{{ $statusText }}</span></td><td><a href="{{ route('sms.recharge.show',$r->token) }}">{{ __('sms.common.view') }}</a></td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </div>
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:28px">{{ __('sms.account.wallet_logs') }}</h2>
            @if($logs->isEmpty())
                <div class="empty">{{ __('sms.common.none') }}</div>
            @else
                <div class="table-wrap"><table class="table" style="min-width:560px"><thead><tr><th>{{ __('sms.common.type') }}</th><th>{{ __('sms.common.amount') }}</th><th>{{ __('sms.common.balance') }}</th><th>{{ __('sms.common.remark') }}</th></tr></thead><tbody>
                    @foreach($logs as $log)<tr><td>{{ $log->type }}</td><td class="mono">{{ (float)$log->amount >= 0 ? '+' : '' }}{{ number_format((float)$log->amount,2) }}</td><td>¥{{ number_format((float)$log->balance_after,2) }}</td><td>{{ $log->remark }}<br><span class="dim">{{ $log->created_at }}</span></td></tr>@endforeach
                </tbody></table></div>
            @endif
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">{{ __('sms.account.sms_orders') }}</h2>
            @if($orders->isEmpty())
                <div class="empty">{{ __('sms.account.empty_orders') }}</div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>{{ __('sms.common.order_no') }}</th><th>{{ __('sms.common.service') }}/{{ __('sms.common.country') }}</th><th>{{ __('sms.common.price') }}</th><th>{{ __('sms.common.status') }}</th><th>{{ __('sms.common.phone') }}</th><th>{{ __('sms.common.code') }}</th><th>{{ __('sms.common.refund') }}</th><th>{{ __('sms.common.action') }}</th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        @php
                            $statusKey = 'sms.status.' . $order->status;
                            $statusText = __($statusKey);
                            if ($statusText === $statusKey) { $statusText = $order->status; }
                        @endphp
                        <tr>
                            <td class="mono">{{ $order->order_sn }}</td>
                            <td>{{ $order->service->name ?? $order->service_code }}<br><span class="dim">{{ $order->country->name ?? $order->country_code }}</span></td>
                            <td>¥{{ number_format((float)$order->sale_price,2) }}</td>
                            <td><span class="status">{{ $statusText }}</span></td>
                            <td class="mono">{{ $order->phone_number ?: '-' }}</td>
                            <td class="mono">{{ $order->sms_code ?: '-' }}</td>
                            <td>{{ $order->wallet_refunded_at ? __('sms.account.refunded_amount', ['amount' => number_format((float)$order->wallet_amount,2)]) : '-' }}</td>
                            <td><a class="btn btn-dark" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">{{ __('sms.common.view') }}</a></td>
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
