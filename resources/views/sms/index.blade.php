@extends('sms.layouts.site')
@section('title', '获取号码 - ZXAIHUB SMS')
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="eyebrow">🧭 获取号码</div>
        <h1 class="section-title" style="text-align:left;margin-bottom:14px">选择平台 → 选择国家 → 显示价格 → 支付取号</h1>
        <p class="section-sub" style="text-align:left;margin-left:0">页面展示缓存价格；提交下单前系统会实时请求 HeroSMS 最新成本。如果成本上涨导致旧价不安全，会自动重新报价，不会按旧价亏本成交。</p>
    </div>
</section>

<section class="section-tight">
    <div class="container catalog-shell">
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:30px">热门平台</h2>
            <p class="muted">点击卡片可快速填入服务，再选择国家。</p>
            @if(empty($catalog['services']))
                <div class="empty">暂无可售服务。请先到后台配置 HeroSMS API Key，并执行 <span class="mono">php artisan sms:sync-prices</span> 同步价格。</div>
            @else
                <div class="service-preview" id="service-list">
                    @foreach($catalog['services'] as $service)
                        @php
                            $items = $catalog['countriesByService'][$service['code']] ?? [];
                            $first = count($items) ? array_values($items)[0] : null;
                        @endphp
                        <button type="button" class="service-item" data-service-pick="{{ $service['code'] }}" style="width:100%;color:#fff;text-align:left;cursor:pointer">
                            <span class="icon">{{ mb_substr($service['name'],0,1) }}</span>
                            <span><b>{{ $service['name'] }}</b><span>{{ $service['code'] }} · {{ count($items) }} 个国家可选</span></span>
                            <span class="service-price">{{ $first ? ('到 ¥'.number_format((float)$first['price'],2)) : '同步中' }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <form method="post" action="{{ route('sms.order.create') }}" class="panel selector-card" id="order-form">
            @csrf
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:22px">
                <div>
                    <h2 style="margin:0;font-size:30px">下单面板</h2>
                    <p class="muted" style="margin:8px 0 0">支付后自动取号并等待验证码。</p>
                </div>
                <span class="pill">实时防亏本</span>
            </div>

            <div class="field">
                <label>平台/服务</label>
                <select name="service_code" id="service" required>
                    <option value="">请选择平台</option>
                    @foreach($catalog['services'] ?? [] as $service)
                        <option value="{{ $service['code'] }}" @if(old('service_code')===$service['code']) selected @endif>{{ $service['name'] }} ({{ $service['code'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>国家/地区</label>
                <select name="country_code" id="country" required><option value="">请先选择平台</option></select>
            </div>

            <div class="panel panel-black" style="margin:22px 0;padding:22px">
                <div class="muted">当前售价</div>
                <div class="price" id="price">¥--</div>
                <div class="muted" id="stock">库存：--</div>
                <input type="hidden" name="displayed_price" id="displayed_price" value="{{ old('displayed_price') }}">
            </div>

            @auth
                <div class="panel panel-black" style="padding:18px;margin-bottom:20px">
                    <b>已登录：{{ auth()->user()->email }}</b>
                    <p class="muted" style="margin:8px 0 0">当前余额：<b style="color:#fff">¥{{ number_format((float)$wallet->balance, 2) }}</b>。订单会自动归档到“我的号码”；无码、无库存、取号失败会退回余额。</p>
                    <div style="margin-top:14px"><a class="btn btn-dark" href="{{ route('sms.recharge.index') }}">余额不足？去充值</a></div>
                </div>
            @else
                <div class="grid">
                    <div class="field">
                        <label>邮箱（可选，推荐）</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="用于订单查询">
                    </div>
                    <div class="field">
                        <label>查询密码（可选）</label>
                        <input type="text" name="query_password" value="{{ old('query_password') }}" placeholder="设置后更安全">
                    </div>
                </div>
                <p class="help">也可以 <a href="{{ route('login') }}" style="color:var(--purple2);font-weight:900">邮箱登录</a> 后下单，自动保存到“我的号码”。</p>
            @endauth

            @auth
                <h3 style="font-size:22px;margin:24px 0 14px">支付方式</h3>
                <label class="pay-card">
                    <input type="radio" name="payment_method" value="balance" checked>
                    <span><b>余额支付</b><br><span class="dim">先从账户余额扣款；失败自动退回余额</span></span>
                </label>
                <button class="btn btn-primary btn-block" type="submit" style="margin-top:24px" @if(empty($catalog['services'])) disabled @endif>确认价格并用余额下单</button>
            @else
                <h3 style="font-size:22px;margin:24px 0 14px">支付方式</h3>
                <div class="err" style="width:100%;margin:0 0 14px">推荐先注册/登录并充值余额，这样无码会自动退回个人账户。游客直付订单无法自动退余额，只能后台人工处理。</div>
                @if(empty($methods))
                    <div class="err" style="width:100%;margin:0">暂无启用支付方式，请先配置易支付或 Epusdt。</div>
                @else
                    <div class="service-grid">
                        @foreach($methods as $code => $method)
                            <label class="pay-card">
                                <input type="radio" name="payment_method" value="{{ $code }}" @if(old('payment_method', array_key_first($methods))===$code) checked @endif>
                                <span><b>{{ $method['name'] }}</b><br><span class="dim">{{ $method['driver'] }}</span></span>
                            </label>
                        @endforeach
                    </div>
                @endif
                <button class="btn btn-primary btn-block" type="submit" style="margin-top:24px" @if(empty($methods) || empty($catalog['services'])) disabled @endif>游客直付下单</button>
            @endauth
        </form>
    </div>
</section>

<section class="section-tight">
    <div class="container grid3">
        <div class="feature-card"><div class="icon">1</div><h3>缓存展示</h3><p>前台展示定时同步的缓存价格，页面打开速度快。</p></div>
        <div class="feature-card"><div class="icon">2</div><h3>实时确认</h3><p>点击下单前再次请求 HeroSMS 成本，价格异常自动拦截。</p></div>
        <div class="feature-card"><div class="icon">3</div><h3>自动等待</h3><p>支付成功后自动取号、轮询验证码，页面无需手动刷新。</p></div>
    </div>
</section>
@endsection
@section('scripts')
<script>
const data = @json($catalog['countriesByService'] ?? []);
const oldService = @json(old('service_code'));
const oldCountry = @json(old('country_code'));
const service = document.getElementById('service');
const country = document.getElementById('country');
const price = document.getElementById('price');
const stock = document.getElementById('stock');
const displayed = document.getElementById('displayed_price');
function renderCountries(){
    const svc = service.value; country.innerHTML = '<option value="">请选择国家</option>';
    if(!svc || !data[svc]) { updatePrice(); highlightService(); return; }
    Object.values(data[svc]).sort((a,b)=>String(a.name).localeCompare(String(b.name))).forEach(item=>{
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = item.name + ' - ¥' + Number(item.price).toFixed(2) + ' / 库存 ' + item.stock;
        if(String(oldCountry) === String(item.id)) opt.selected = true;
        country.appendChild(opt);
    });
    updatePrice(); highlightService();
}
function updatePrice(){
    const svc = service.value, c = country.value;
    if(svc && c && data[svc] && data[svc][c]){
        const item=data[svc][c];
        price.textContent='¥'+Number(item.price).toFixed(2);
        stock.textContent='库存：'+item.stock+'；同步：'+(item.synced_at||'-');
        displayed.value=Number(item.price).toFixed(2);
    } else { price.textContent='¥--'; stock.textContent='库存：--'; displayed.value=''; }
}
function highlightService(){
    document.querySelectorAll('[data-service-pick]').forEach(btn=>{
        btn.style.borderColor = btn.dataset.servicePick === service.value ? 'rgba(154,124,255,.8)' : 'rgba(255,255,255,.06)';
        btn.style.background = btn.dataset.servicePick === service.value ? 'rgba(118,87,240,.18)' : '#1d1e25';
    });
}
document.querySelectorAll('[data-service-pick]').forEach(btn=>btn.addEventListener('click',()=>{service.value=btn.dataset.servicePick; renderCountries(); document.getElementById('order-form').scrollIntoView({behavior:'smooth',block:'start'});}));
service.addEventListener('change', renderCountries); country.addEventListener('change', updatePrice);
if(oldService) service.value = oldService;
renderCountries();
</script>
@endsection
