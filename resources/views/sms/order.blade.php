@extends('sms.layouts.site')
@section('title', '订单 '.$order->order_sn.' - ZXAIHUB SMS')
@php
$statusMap = [
 'wait_pay'=>'待支付','paid'=>'已支付，准备取号','purchasing_number'=>'正在获取号码','waiting_code'=>'等待验证码','completed'=>'已完成','price_changed'=>'价格已变化','provider_no_stock'=>'HeroSMS 无库存','refund_required'=>'需人工处理/退款','cancelled'=>'已取消','expired'=>'已过期','failed'=>'失败'
];
$statusMap['refunded'] = '已退回余额';
$payment = $order->latestPayment;
$donePay = !empty($order->paid_at);
$donePhone = !empty($order->phone_number);
$doneCode = !empty($order->sms_code);
@endphp
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="panel">
            <div class="order-head">
                <div>
                    <div class="eyebrow">📄 接码订单</div>
                    <h1 style="font-size:clamp(32px,4vw,56px);margin:0 0 12px" class="mono">{{ $order->order_sn }}</h1>
                    <div style="display:flex;gap:10px;flex-wrap:wrap">
                        <span class="pill">{{ $order->service->name ?? $order->service_code }}</span>
                        <span class="pill">{{ $order->country->name ?? $order->country_code }}</span>
                        <span class="status">{{ $statusMap[$order->status] ?? $order->status }}</span>
                    </div>
                </div>
                <div style="text-align:right">
                    <div class="muted">订单金额</div>
                    <div class="price">¥{{ number_format((float)$order->sale_price, 2) }}</div>
                </div>
            </div>
            @if($order->status_note)<p class="err" style="width:100%;margin:22px 0 0">{{ $order->status_note }}</p>@endif
            <div class="order-timeline">
                <div class="time-step done"><b>1. 已报价</b><br><span>实时成本确认</span></div>
                <div class="time-step {{ $donePay ? 'done' : '' }}"><b>2. 支付</b><br><span>{{ $donePay ? optional($order->paid_at)->format('H:i:s') : '等待支付' }}</span></div>
                <div class="time-step {{ $donePhone ? 'done' : '' }}"><b>3. 取号</b><br><span>{{ $donePhone ? '号码已获取' : '支付后自动取号' }}</span></div>
                <div class="time-step {{ $doneCode ? 'done' : '' }}"><b>4. 验证码</b><br><span>{{ $doneCode ? '验证码已到达' : '等待短信' }}</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section-tight">
    <div class="container grid">
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:30px">支付信息</h2>
            @if($payment)
                <p><span class="muted">支付方式：</span>{{ $payment->method_code }} / {{ $payment->status }}</p>
                <p><span class="muted">支付单号：</span><span class="mono">{{ $payment->payment_sn }}</span></p>
                @if($order->status === 'wait_pay' && $payment->status === 'pending')
                    <p class="muted">请在 {{ optional($order->expires_at)->toDateTimeString() }} 前完成支付，过期需重新下单。</p>
                    <a class="btn btn-primary btn-block" href="{{ route('sms.pay.gateway', ['methodCode'=>$payment->method_code, 'paymentSn'=>$payment->payment_sn]) }}">立即支付</a>
                @elseif($payment->status === 'paid')
                    <div class="ok" style="width:100%;margin:14px 0 0">已支付：{{ optional($payment->paid_at)->toDateTimeString() }}</div>
                @endif
            @else
                @if($order->wallet_paid_at)
                    <p><span class="muted">支付方式：</span>余额支付</p>
                    <p><span class="muted">扣款金额：</span>¥{{ number_format((float)$order->wallet_amount, 2) }}</p>
                    <div class="ok" style="width:100%;margin:14px 0 0">已从余额扣款：{{ optional($order->wallet_paid_at)->toDateTimeString() }}</div>
                    @if($order->wallet_refunded_at)
                        <div class="err" style="width:100%;margin:14px 0 0">已退回余额：{{ optional($order->wallet_refunded_at)->toDateTimeString() }}；原因：{{ $order->wallet_refund_reason }}</div>
                    @endif
                @else
                    <p class="muted">没有找到支付单。</p>
                @endif
            @endif
            <div style="margin-top:20px;display:flex;gap:12px;flex-wrap:wrap">
                <a class="btn btn-dark" href="{{ route('sms.index') }}">再买一个</a>
                <a class="btn btn-ghost" href="{{ route('sms.query') }}">查询订单</a>
            </div>
        </div>

        <div class="panel">
            <h2 style="margin-top:0;font-size:30px">号码与验证码</h2>
            <div class="field">
                <label>手机号码</label>
                <div class="copy-row"><input class="mono" id="phone" readonly value="{{ $order->phone_number }}" placeholder="支付后自动获取"><button type="button" class="btn btn-dark" data-copy="phone">复制号码</button></div>
            </div>
            <div class="field">
                <label>验证码</label>
                <div class="copy-row"><input class="mono" id="code" readonly value="{{ $order->sms_code }}" placeholder="等待短信验证码"><button type="button" class="btn btn-dark" data-copy="code">复制验证码</button></div>
            </div>
            <div class="field">
                <label>短信内容</label>
                <textarea id="sms_text" rows="4" readonly placeholder="完整短信内容会显示在这里">{{ $order->sms_text }}</textarea>
            </div>
            <p class="muted" id="polling-text">页面会自动刷新订单状态。</p>
        </div>
    </div>
</section>

@if(in_array($order->status, ['waiting_code','purchasing_number']))
<section class="section-tight">
    <div class="container">
        <form method="post" action="{{ route('sms.order.cancel', ['token'=>$order->token]) }}" onsubmit="return confirm('确认取消？')" class="panel panel-black">
            @csrf
            <button class="btn btn-danger" type="submit">取消订单</button>
            <span class="muted" style="margin-left:12px">仅未完成接码时可取消。</span>
        </form>
    </div>
</section>
@endif
@endsection
@section('scripts')
<script>
const terminal = ['completed','cancelled','expired','refund_required','provider_no_stock','failed','refunded'];
function copy(id){const el=document.getElementById(id); if(!el || !el.value)return; navigator.clipboard.writeText(el.value);}
document.querySelectorAll('[data-copy]').forEach(btn=>btn.addEventListener('click',()=>copy(btn.dataset.copy)));
async function poll(){
    try{
        const res = await fetch(@json(route('sms.order.status', ['token'=>$order->token])), {headers:{'Accept':'application/json'}});
        const data = await res.json();
        const badge = document.querySelector('.status');
        if(badge) badge.textContent = data.status_text || data.status;
        if(data.phone_number) document.getElementById('phone').value=data.phone_number;
        if(data.sms_code) document.getElementById('code').value=data.sms_code;
        if(data.sms_text) document.getElementById('sms_text').value=data.sms_text;
        document.getElementById('polling-text').textContent='状态：'+(data.status_text||data.status)+'；最后检查：'+new Date().toLocaleTimeString();
        if(!terminal.includes(data.status)) setTimeout(poll, 8000);
    }catch(e){ setTimeout(poll, 12000); }
}
@if(!in_array($order->status, ['completed','cancelled','expired','refund_required','provider_no_stock','failed','refunded']))
setTimeout(poll, 3000);
@endif
</script>
@endsection
