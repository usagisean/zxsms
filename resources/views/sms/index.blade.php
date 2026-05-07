@extends('sms.layouts.site')
@section('title', __('sms.sms.title'))
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="eyebrow">{{ __('sms.sms.eyebrow') }}</div>
        <h1 class="section-title" style="text-align:left;margin-bottom:14px">{{ __('sms.sms.headline') }}</h1>
        <p class="section-sub" style="text-align:left;margin-left:0">{{ __('sms.sms.sub') }}</p>
    </div>
</section>

<section class="section-tight">
    <div class="container catalog-shell">
        <div class="panel panel-black">
            <h2 style="margin-top:0;font-size:30px">{{ __('sms.sms.popular') }}</h2>
            <p class="muted">{{ __('sms.sms.popular_tip') }}</p>
            @if(empty($catalog['services']))
                <div class="empty">{{ __('sms.sms.empty') }}</div>
            @else
                <div class="service-preview" id="service-list">
                    @foreach($catalog['services'] as $service)
                        @php
                            $items = $catalog['countriesByService'][$service['code']] ?? [];
                            $first = count($items) ? array_values($items)[0] : null;
                        @endphp
                        <button type="button" class="service-item" data-service-pick="{{ $service['code'] }}" style="width:100%;color:#fff;text-align:left;cursor:pointer">
                            <span class="icon">{{ mb_substr($service['name'],0,1) }}</span>
                            <span><b>{{ $service['name'] }}</b><span>{{ $service['code'] }} · {{ count($items) }} {{ __('sms.sms.countries_available') }}</span></span>
                            <span class="service-price">{{ $first ? __('sms.sms.from_price', ['price'=>number_format((float)$first['price'],2)]) : __('sms.common.loading') }}</span>
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <form method="post" action="{{ route('sms.order.create') }}" class="panel selector-card" id="order-form">
            @csrf
            <div style="display:flex;justify-content:space-between;gap:16px;align-items:center;margin-bottom:22px">
                <div>
                    <h2 style="margin:0;font-size:30px">{{ __('sms.sms.order_panel') }}</h2>
                    <p class="muted" style="margin:8px 0 0">{{ __('sms.sms.order_tip') }}</p>
                </div>
                <span class="pill">{{ __('sms.sms.anti_loss') }}</span>
            </div>

            <div class="field">
                <label>{{ __('sms.sms.service') }}</label>
                <select name="service_code" id="service" required>
                    <option value="">{{ __('sms.sms.select_service') }}</option>
                    @foreach($catalog['services'] ?? [] as $service)
                        <option value="{{ $service['code'] }}" @if(old('service_code')===$service['code']) selected @endif>{{ $service['name'] }} ({{ $service['code'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label>{{ __('sms.sms.country') }}</label>
                <select name="country_code" id="country" required><option value="">{{ __('sms.sms.select_country_first') }}</option></select>
            </div>

            <div class="panel panel-black" style="margin:22px 0;padding:22px">
                <div class="muted">{{ __('sms.sms.current_price') }}</div>
                <div class="price" id="price">¥--</div>
                <div class="muted" id="stock">{{ __('sms.common.stock') }}：--</div>
                <input type="hidden" name="displayed_price" id="displayed_price" value="{{ old('displayed_price') }}">
            </div>

            @auth
                <div class="panel panel-black" style="padding:18px;margin-bottom:20px">
                    <b>{{ __('sms.sms.logged_in', ['email'=>auth()->user()->email]) }}</b>
                    <p class="muted" style="margin:8px 0 0">{!! __('sms.sms.wallet_tip', ['balance'=>number_format((float)$wallet->balance, 2)]) !!}</p>
                    <div style="margin-top:14px"><a class="btn btn-dark" href="{{ route('sms.recharge.index') }}">{{ __('sms.sms.need_recharge') }}</a></div>
                </div>
            @else
                <div class="grid">
                    <div class="field">
                        <label>{{ __('sms.sms.email_optional') }}</label>
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('sms.sms.email_placeholder') }}">
                    </div>
                    <div class="field">
                        <label>{{ __('sms.sms.query_password') }}</label>
                        <input type="text" name="query_password" value="{{ old('query_password') }}" placeholder="{{ __('sms.sms.query_password_placeholder') }}">
                    </div>
                </div>
                <p class="help">{{ __('sms.sms.login_hint_1') }} <a href="{{ route('login') }}" style="color:var(--purple2);font-weight:900">{{ __('sms.nav.email_login') }}</a> {{ __('sms.sms.login_hint_2') }}</p>
            @endauth

            @auth
                <h3 style="font-size:22px;margin:24px 0 14px">{{ __('sms.sms.payment_method') }}</h3>
                <label class="pay-card">
                    <input type="radio" name="payment_method" value="balance" checked>
                    <span><b>{{ __('sms.sms.balance_pay') }}</b><br><span class="dim">{{ __('sms.sms.balance_desc') }}</span></span>
                </label>
                <button class="btn btn-primary btn-block" type="submit" style="margin-top:24px" @if(empty($catalog['services'])) disabled @endif>{{ __('sms.sms.balance_submit') }}</button>
            @else
                <h3 style="font-size:22px;margin:24px 0 14px">{{ __('sms.sms.payment_method') }}</h3>
                <div class="err" style="width:100%;margin:0 0 14px">{{ __('sms.sms.guest_warning') }}</div>
                @if(empty($methods))
                    <div class="err" style="width:100%;margin:0">{{ __('sms.sms.no_pay_methods') }}</div>
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
                <button class="btn btn-primary btn-block" type="submit" style="margin-top:24px" @if(empty($methods) || empty($catalog['services'])) disabled @endif>{{ __('sms.sms.guest_submit') }}</button>
            @endauth
        </form>
    </div>
</section>

<section class="section-tight">
    <div class="container grid3">
        <div class="feature-card"><div class="icon">1</div><h3>{{ __('sms.sms.feature_cache') }}</h3><p>{{ __('sms.sms.feature_cache_desc') }}</p></div>
        <div class="feature-card"><div class="icon">2</div><h3>{{ __('sms.sms.feature_live') }}</h3><p>{{ __('sms.sms.feature_live_desc') }}</p></div>
        <div class="feature-card"><div class="icon">3</div><h3>{{ __('sms.sms.feature_wait') }}</h3><p>{{ __('sms.sms.feature_wait_desc') }}</p></div>
    </div>
</section>
@endsection
@section('scripts')
<script>
const data = @json($catalog['countriesByService'] ?? []);
const oldService = @json(old('service_code'));
const oldCountry = @json(old('country_code'));
const i18n = {
    selectCountry: @json(__('sms.sms.select_country')),
    stockLine: @json(__('sms.sms.js_stock')),
    countryOption: @json(__('sms.sms.js_country_option'))
};
const service = document.getElementById('service');
const country = document.getElementById('country');
const price = document.getElementById('price');
const stock = document.getElementById('stock');
const displayed = document.getElementById('displayed_price');
function tpl(text, vars){return Object.keys(vars).reduce((s,k)=>s.replace(':'+k, vars[k]), text);}
function renderCountries(){
    const svc = service.value; country.innerHTML = '<option value="">'+i18n.selectCountry+'</option>';
    if(!svc || !data[svc]) { updatePrice(); highlightService(); return; }
    Object.values(data[svc]).sort((a,b)=>String(a.name).localeCompare(String(b.name))).forEach(item=>{
        const opt = document.createElement('option');
        opt.value = item.id;
        opt.textContent = tpl(i18n.countryOption, {name:item.name, price:Number(item.price).toFixed(2), stock:item.stock});
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
        stock.textContent=tpl(i18n.stockLine, {stock:item.stock, time:item.synced_at||'-'});
        displayed.value=Number(item.price).toFixed(2);
    } else { price.textContent='¥--'; stock.textContent='{{ __('sms.common.stock') }}：--'; displayed.value=''; }
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
