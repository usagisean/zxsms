@extends('sms.layouts.site')
@section('title', __('sms.query.title'))
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="eyebrow">{{ __('sms.query.eyebrow') }}</div>
            <h1 style="font-size:clamp(34px,5vw,62px);margin:0 0 14px">{{ __('sms.query.headline') }}</h1>
            <p class="muted" style="font-size:18px;line-height:1.8">{{ __('sms.query.sub') }}</p>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container">
        <form class="panel panel-black" method="post" action="{{ route('sms.query.post') }}">
            @csrf
            <div class="grid3">
                <div class="field"><label>{{ __('sms.query.order_sn') }}</label><input name="order_sn" value="{{ old('order_sn') }}" placeholder="{{ __('sms.query.order_sn_placeholder') }}"></div>
                <div class="field"><label>{{ __('sms.query.email') }}</label><input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('sms.query.email_placeholder') }}"></div>
                <div class="field"><label>{{ __('sms.query.query_password') }}</label><input name="query_password" value="{{ old('query_password') }}" placeholder="{{ __('sms.query.query_password_placeholder') }}"></div>
            </div>
            <button class="btn btn-primary" type="submit">{{ __('sms.query.submit') }}</button>
        </form>
    </div>
</section>

@if($orders !== null)
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">{{ __('sms.query.results') }}</h2>
            @if($orders->isEmpty())
                <div class="empty">{{ __('sms.query.empty') }}</div>
            @else
                <div class="table-wrap"><table class="table">
                    <thead><tr><th>{{ __('sms.common.order_no') }}</th><th>{{ __('sms.common.service') }}</th><th>{{ __('sms.common.country') }}</th><th>{{ __('sms.common.status') }}</th><th>{{ __('sms.common.phone') }}</th><th>{{ __('sms.common.code') }}</th><th>{{ __('sms.common.action') }}</th></tr></thead>
                    <tbody>
                    @foreach($orders as $order)
                        @php
                            $statusKey = 'sms.status.' . $order->status;
                            $statusText = __($statusKey);
                            if ($statusText === $statusKey) { $statusText = $order->status; }
                        @endphp
                        <tr>
                            <td class="mono">{{ $order->order_sn }}</td>
                            <td>{{ $order->service->name ?? $order->service_code }}</td>
                            <td>{{ $order->country->name ?? $order->country_code }}</td>
                            <td><span class="status">{{ $statusText }}</span></td>
                            <td class="mono">{{ $order->phone_number ?: '-' }}</td>
                            <td class="mono">{{ $order->sms_code ?: '-' }}</td>
                            <td><a class="btn btn-dark" href="{{ route('sms.order.show', ['token'=>$order->token]) }}">{{ __('sms.common.view') }}</a></td>
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
