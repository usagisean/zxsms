@extends('sms.layouts.site')
@section('title', __('sms.sms.title'))
@section('content')
<section class="section-tight">
    <div class="container">
        <div class="eyebrow">{{ __('sms.sms.eyebrow') }}</div>
        <h1 class="section-title">{{ __('sms.sms.headline') }}</h1>
        <p class="section-sub">{{ __('sms.sms.sub') }}</p>
    </div>
</section>

<section class="section-tight" style="padding-top:10px">
    <div class="container">
        <div class="guide-strip" aria-label="{{ __('sms.sms.guide_label') }}">
            <div class="guide-step"><span>01</span><b>{{ __('sms.sms.guide_search') }}</b><small>{{ __('sms.sms.guide_search_desc') }}</small></div>
            <div class="guide-step"><span>02</span><b>{{ __('sms.sms.guide_country') }}</b><small>{{ __('sms.sms.guide_country_desc') }}</small></div>
            <div class="guide-step"><span>03</span><b>{{ __('sms.sms.guide_quote') }}</b><small>{{ __('sms.sms.guide_quote_desc') }}</small></div>
            <div class="guide-step"><span>04</span><b>{{ __('sms.sms.guide_pay') }}</b><small>{{ __('sms.sms.guide_pay_desc') }}</small></div>
        </div>
    </div>
    <div class="container catalog-shell">
        <div class="panel panel-black">
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">{{ __('sms.sms.popular') }}</h2>
                    <p class="panel-sub">{{ __('sms.sms.popular_tip') }}</p>
                </div>
                <span class="pill">{{ __('sms.sms.step_1') }}</span>
            </div>
            @if(empty($catalog['services']))
                <div class="empty">{{ __('sms.sms.empty') }}</div>
            @else
                <div class="search-box">
                    <input id="service-search" type="search" autocomplete="off" placeholder="{{ __('sms.sms.search_placeholder') }}" aria-label="{{ __('sms.sms.search_service') }}">
                </div>
                <div class="service-preview" id="service-list">
                    @foreach($catalog['services'] as $service)
                        @php
                            $items = $catalog['countriesByService'][$service['code']] ?? [];
                            $first = count($items) ? array_values($items)[0] : null;
                        @endphp
                        <button type="button" class="service-item" data-service-pick="{{ $service['code'] }}" data-service-name="{{ $service['name'] }}" data-search="{{ mb_strtolower($service['name'] . ' ' . $service['code']) }}">
                            <span class="icon">{{ mb_substr($service['name'],0,1) }}</span>
                            <span><b>{{ $service['name'] }}</b><span>{{ $service['code'] }} · {{ count($items) }} {{ __('sms.sms.countries_available') }}</span></span>
                            <span class="service-price">{{ $first ? __('sms.sms.from_price', ['price'=>number_format((float)$first['price'],2)]) : __('sms.common.loading') }}</span>
                        </button>
                    @endforeach
                </div>
                <div class="empty" id="service-empty" style="display:none;margin-top:12px">{{ __('sms.sms.no_search_results') }}</div>
            @endif
        </div>

        <form method="post" action="{{ route('sms.order.create') }}" class="panel selector-card" id="order-form">
            @csrf
            <div class="panel-head">
                <div>
                    <h2 class="panel-title">{{ __('sms.sms.order_panel') }}</h2>
                    <p class="panel-sub">{{ __('sms.sms.order_tip') }}</p>
                </div>
                <span class="pill">{{ __('sms.sms.anti_loss') }}</span>
            </div>

            <div class="flow-steps" aria-label="{{ __('sms.sms.flow_label') }}">
                <div class="flow-step is-on" data-step="service">1. {{ __('sms.sms.step_service') }}</div>
                <div class="flow-step" data-step="country">2. {{ __('sms.sms.step_country') }}</div>
                <div class="flow-step" data-step="pay">3. {{ __('sms.sms.step_pay') }}</div>
            </div>

            <div class="mini-summary">
                <div class="summary-row"><span>{{ __('sms.sms.selected_service') }}</span><b id="summary-service">--</b></div>
                <div class="summary-row"><span>{{ __('sms.sms.selected_country') }}</span><b id="summary-country">--</b></div>
            </div>

            <div class="field">
                <div class="field-head">
                    <label>{{ __('sms.sms.service') }}</label>
                    <span>{{ __('sms.sms.service_hint') }}</span>
                </div>
                <select name="service_code" id="service" required>
                    <option value="">{{ __('sms.sms.select_service') }}</option>
                    @foreach($catalog['services'] ?? [] as $service)
                        <option value="{{ $service['code'] }}" @if(old('service_code')===$service['code']) selected @endif>{{ $service['name'] }} ({{ $service['code'] }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <div class="field-head">
                    <label>{{ __('sms.sms.country') }}</label>
                    <span>{{ __('sms.sms.country_hint') }}</span>
                </div>
                <select name="country_code" id="country" required><option value="">{{ __('sms.sms.select_country_first') }}</option></select>
            </div>

            <div class="field">
                <div class="field-head">
                    <label>{{ __('sms.sms.package') }}</label>
                    <span>{{ __('sms.sms.package_hint') }}</span>
                </div>
                <div class="package-choice">
                    <span class="package-dot">1</span>
                    <span><b>{{ __('sms.sms.package_once') }}</b><small>{{ __('sms.sms.package_once_desc') }}</small></span>
                    <span class="package-tag" id="package-tag">{{ __('sms.sms.package_waiting') }}</span>
                </div>
            </div>

            <div class="panel panel-black" style="margin:18px 0;padding:18px">
                <div class="muted">{{ __('sms.sms.current_price') }}</div>
                <div class="price" id="price">¥--</div>
                <div class="muted" id="stock">{{ __('sms.common.stock') }}：--</div>
                <input type="hidden" name="displayed_price" id="displayed_price" value="{{ old('displayed_price') }}">
            </div>

            <div id="identity-block" class="form-section is-muted">
                @auth
                    <div class="panel panel-black" style="padding:18px;margin-bottom:0">
                        <b>{{ __('sms.sms.logged_in', ['email'=>auth()->user()->email]) }}</b>
                        <p class="muted" style="margin:8px 0 0">{!! __('sms.sms.wallet_tip', ['balance'=>number_format((float)$wallet->balance, 2)]) !!}</p>
                        <div style="margin-top:14px"><a class="btn btn-dark" href="{{ route('sms.recharge.index') }}">{{ __('sms.sms.need_recharge') }}</a></div>
                    </div>
                @else
                    <div class="field-row">
                        <div class="field">
                            <label>{{ __('sms.sms.email_optional') }}</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('sms.sms.email_placeholder') }}">
                        </div>
                        <div class="field">
                            <label>{{ __('sms.sms.query_password') }}</label>
                            <input type="text" name="query_password" value="{{ old('query_password') }}" placeholder="{{ __('sms.sms.query_password_placeholder') }}">
                        </div>
                    </div>
                    <p class="help">{{ __('sms.sms.login_hint_1') }} <a href="{{ route('login') }}" style="color:var(--accent2);font-weight:900">{{ __('sms.nav.email_login') }}</a> {{ __('sms.sms.login_hint_2') }}</p>
                @endauth
            </div>

            <div id="payment-block" class="form-section" style="display:none">
                @auth
                    <h3 class="payment-title" style="font-size:20px;margin:0 0 12px;letter-spacing:-.02em">{{ __('sms.sms.payment_method') }}</h3>
                    <label class="pay-card">
                        <input type="radio" name="payment_method" value="balance" checked>
                        <span><b>{{ __('sms.sms.balance_pay') }}</b><br><span class="dim">{{ __('sms.sms.balance_desc') }}</span></span>
                    </label>
                    <button class="btn btn-primary btn-block" type="button" data-open-confirm style="margin-top:18px" @if(empty($catalog['services'])) disabled @endif>{{ __('sms.sms.continue_pay') }}</button>
                @else
                    <h3 class="payment-title" style="font-size:20px;margin:0 0 12px;letter-spacing:-.02em">{{ __('sms.sms.payment_method') }}</h3>
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
                    <button class="btn btn-primary btn-block" type="button" data-open-confirm style="margin-top:18px" @if(empty($methods) || empty($catalog['services'])) disabled @endif>{{ __('sms.sms.continue_pay') }}</button>
                @endauth
            </div>

            <div class="confirm-modal" id="confirm-modal" aria-hidden="true">
                <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="confirm-title">
                    <h3 id="confirm-title">{{ __('sms.sms.confirm_title') }}</h3>
                    <p class="muted" style="line-height:1.7;margin:0 0 14px">{{ __('sms.sms.confirm_sub') }}</p>
                    <div class="mini-summary">
                        <div class="summary-row"><span>{{ __('sms.sms.selected_service') }}</span><b id="modal-service">--</b></div>
                        <div class="summary-row"><span>{{ __('sms.sms.selected_country') }}</span><b id="modal-country">--</b></div>
                        <div class="summary-row"><span>{{ __('sms.common.price') }}</span><b id="modal-price">¥--</b></div>
                    </div>
                    <div class="modal-actions">
                        <button type="button" class="btn btn-dark btn-block" data-close-confirm>{{ __('sms.sms.back_edit') }}</button>
                        <button class="btn btn-primary btn-block" type="submit" @if((!auth()->check() && empty($methods)) || empty($catalog['services'])) disabled @endif>{{ auth()->check() ? __('sms.sms.balance_submit') : __('sms.sms.guest_submit') }}</button>
                    </div>
                </div>
            </div>
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
    countryOption: @json(__('sms.sms.js_country_option')),
    none: @json(__('sms.common.none'))
};
const orderForm = document.getElementById('order-form');
const service = document.getElementById('service');
const country = document.getElementById('country');
const price = document.getElementById('price');
const stock = document.getElementById('stock');
const displayed = document.getElementById('displayed_price');
const packageTag = document.getElementById('package-tag');
const summaryService = document.getElementById('summary-service');
const summaryCountry = document.getElementById('summary-country');
const paymentBlock = document.getElementById('payment-block');
const identityBlock = document.getElementById('identity-block');
const modal = document.getElementById('confirm-modal');
function tpl(text, vars){return Object.keys(vars).reduce((s,k)=>s.replace(':'+k, vars[k]), text);}
function serviceLabel(){return service.selectedOptions[0] && service.value ? service.selectedOptions[0].textContent : '--';}
function countryLabel(){return country.selectedOptions[0] && country.value ? country.selectedOptions[0].textContent : '--';}
function setStep(name, on){const el=document.querySelector('[data-step="'+name+'"]'); if(el) el.classList.toggle('is-on', !!on);}
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
    summaryService.textContent = serviceLabel();
    summaryCountry.textContent = countryLabel();
    setStep('country', !!svc);
    setStep('pay', !!(svc && c));
    if(identityBlock) identityBlock.classList.toggle('is-muted', !(svc && c));
    if(paymentBlock) paymentBlock.style.display = svc && c ? '' : 'none';
    if(svc && c && data[svc] && data[svc][c]){
        const item=data[svc][c];
        price.textContent='¥'+Number(item.price).toFixed(2);
        stock.textContent=tpl(i18n.stockLine, {stock:item.stock, time:item.synced_at||'-'});
        displayed.value=Number(item.price).toFixed(2);
        if(packageTag) packageTag.textContent='¥'+Number(item.price).toFixed(2);
    } else { price.textContent='¥--'; stock.textContent='{{ __('sms.common.stock') }}：--'; displayed.value=''; if(packageTag) packageTag.textContent=@json(__('sms.sms.package_waiting')); }
}
function highlightService(){
    document.querySelectorAll('[data-service-pick]').forEach(btn=>{
        btn.classList.toggle('is-active', btn.dataset.servicePick === service.value);
    });
}
document.querySelectorAll('[data-service-pick]').forEach(btn=>btn.addEventListener('click',()=>{
    service.value=btn.dataset.servicePick;
    renderCountries();
    country.focus({preventScroll:true});
    document.getElementById('order-form').scrollIntoView({behavior:'smooth',block:'start'});
}));
const search = document.getElementById('service-search');
const empty = document.getElementById('service-empty');
if(search){search.addEventListener('input', function(){
    const q=this.value.trim().toLowerCase(); let shown=0;
    document.querySelectorAll('[data-service-pick]').forEach(btn=>{
        const ok=!q || (btn.dataset.search||'').toLowerCase().includes(q);
        btn.style.display=ok?'grid':'none'; if(ok) shown++;
    });
    if(empty) empty.style.display=shown ? 'none' : '';
});}
service.addEventListener('change', renderCountries); country.addEventListener('change', updatePrice);
document.querySelectorAll('[data-open-confirm]').forEach(btn=>btn.addEventListener('click', function(){
    if(orderForm.reportValidity && !orderForm.reportValidity()) return;
    if(!displayed.value) return;
    document.getElementById('modal-service').textContent = serviceLabel();
    document.getElementById('modal-country').textContent = countryLabel();
    document.getElementById('modal-price').textContent = price.textContent;
    modal.classList.add('open'); modal.setAttribute('aria-hidden','false');
}));
document.querySelectorAll('[data-close-confirm]').forEach(btn=>btn.addEventListener('click', function(){modal.classList.remove('open'); modal.setAttribute('aria-hidden','true');}));
if(modal){modal.addEventListener('click', function(e){if(e.target===modal){modal.classList.remove('open'); modal.setAttribute('aria-hidden','true');}});}
if(oldService) service.value = oldService;
renderCountries();
</script>
@endsection
