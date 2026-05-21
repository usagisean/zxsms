@extends('sms.layouts.site')
@section('title', __('sms.landing.title'))
@section('body_class','buy-page')
@section('content')
@php
    $services = $catalog['services'] ?? [];
    $countriesByService = $catalog['countriesByService'] ?? [];
    $walletBalance = $wallet ? (float) $wallet->balance : 0;
    
    $products = [];
    foreach ($services as $service) {
        $items = $countriesByService[$service['code']] ?? [];
        foreach ($items as $countryCode => $item) {
            $products[] = [
                'service_code' => $service['code'],
                'service_name' => $service['name'],
                'country_code' => $countryCode,
                'country_name' => $item['name'],
                'price' => (float) $item['price'],
                'stock' => (int) $item['stock'],
                'title' => !empty($item['title']) ? $item['title'] : "{$service['name']} - {$item['name']}",
                'description' => !empty($item['description']) ? $item['description'] : __('sms.landing.product_desc_template', ['service' => $service['name'], 'region' => $item['name']]),
                'sold_count' => (int) ($item['sold_count'] ?? 0),
                'max_quantity' => max(1, min(50, (int) ($item['max_quantity'] ?? 10))),
            ];
        }
    }
    $productCount = count($products);

    $smsSettings = app(\App\Services\Sms\SmsSettingService::class);
    $supportTgUrl = $smsSettings->get('support_tg_url', '');
    $supportTgLabel = $smsSettings->get('support_tg_label', __('sms.landing.support_tg'));
    $communityTgUrl = $smsSettings->get('community_tg_url', '');
    $communityTgLabel = $smsSettings->get('community_tg_label', __('sms.landing.community_tg'));
    $productValidityDays = (int) $smsSettings->get('product_validity_days', 60);
    $productMinValidityDays = (int) $smsSettings->get('product_min_validity_days', 30);
    $productLongTermNote = $smsSettings->get('product_long_term_note', __('sms.landing.long_term_default'));
@endphp

<section class="buy-zone anchor-offset" id="buy-zone" style="padding-top: 3rem;">
    <div class="container">
        <div class="market-panel" style="width:100%; border:none; background:transparent; padding:0;">
            <div class="panel-head" style="margin-bottom: 24px;">
                <div><h2 class="panel-title">{{ __('sms.landing.market_title') }}</h2><p class="panel-sub">{{ __('sms.landing.market_sub') }}</p></div>
                <span class="pill">{{ __('sms.landing.product_count', ['count' => $productCount]) }}</span>
            </div>
            
            @if(empty($products))
                <div class="empty-market" style="background:rgba(255,255,255,0.02);border:1px solid var(--border-strong);border-radius:var(--radius-lg);padding:4rem;text-align:center;">
                    <div>
                        <h3>{{ __('sms.landing.empty_title') }}</h3>
                        <p>{{ __('sms.landing.empty_sub') }}</p>
                        <a class="btn btn-primary" href="{{ route('sms.query') }}">{{ __('sms.landing.secondary_cta') }}</a>
                    </div>
                </div>
            @else
                <div class="product-toolbar" style="justify-content: space-between;">
                    <div class="search-box" style="flex:1; max-width: 400px;"><input id="product-search" type="search" placeholder="{{ __('sms.landing.search_products_placeholder') }}" style="width:100%;"></div>
                    @auth
                    <div style="display:flex; align-items:center; gap: 16px;">
                        <span class="muted">{{ __('sms.landing.account_balance') }}: <b style="color:var(--accent-cyan)">¥{{ number_format($walletBalance, 2) }}</b></span>
                        <a class="btn btn-dark" href="{{ route('sms.recharge.index') }}">{{ __('sms.nav.recharge') }}</a>
                    </div>
                    @endauth
                </div>
                
                <div class="product-grid-enhanced" id="product-list">
                    @foreach($products as $idx => $prod)
                        @php
                            $actualMax = min($prod['max_quantity'], max(1, $prod['stock']));
                            $isOutOfStock = $prod['stock'] <= 0;
                            $buyPayload = [
                                'service_code' => $prod['service_code'],
                                'country_code' => $prod['country_code'],
                                'title' => $prod['title'],
                                'price' => $prod['price'],
                                'qtyId' => 'qty-' . $idx,
                                'maxQty' => $actualMax,
                            ];
                        @endphp
                        <div class="product-card-enhanced" data-search="{{ mb_strtolower($prod['title'] . ' ' . $prod['service_name'] . ' ' . $prod['country_name']) }}">
                            <div class="pc-head">
                                <span class="pc-logo">{{ mb_substr($prod['service_name'], 0, 1) }}</span>
                                <div class="pc-titles">
                                    <h3>{{ $prod['title'] }}</h3>
                                    <span>{{ $prod['service_name'] }} · {{ $prod['country_name'] }}</span>
                                </div>
                            </div>
                            <p class="pc-desc">{{ $prod['description'] }}</p>
                            
                            <div class="pc-stats">
                                <div><b style="color:var(--accent-cyan); font-size:1.5rem;">¥{{ number_format($prod['price'], 2) }}</b></div>
                                <div style="text-align:right;">
                                    <span class="muted" style="display:block; font-size:0.75rem;">{{ __('sms.landing.stock_label') }}: <span style="color:#e2e8f0">{{ $prod['stock'] }}</span></span>
                                    <span class="muted" style="display:block; font-size:0.75rem;">{{ __('sms.landing.sold_label') }}: <span style="color:#e2e8f0">{{ $prod['sold_count'] }}</span></span>
                                </div>
                            </div>
                            
                            <div class="pc-actions">
                                <div class="quantity-picker" @if($isOutOfStock) style="opacity:0.5;pointer-events:none;" @endif>
                                    <button type="button" class="qp-btn" onclick="qtyChange('qty-{{$idx}}', -1, {{$actualMax}})">-</button>
                                    <input type="number" id="qty-{{$idx}}" class="qp-input" value="{{ $isOutOfStock ? 0 : 1 }}" min="1" max="{{$actualMax}}" readonly>
                                    <button type="button" class="qp-btn" onclick="qtyChange('qty-{{$idx}}', 1, {{$actualMax}})">+</button>
                                </div>
                                @if($isOutOfStock)
                                    <button type="button" class="btn btn-dark" style="flex:1; cursor:not-allowed;" disabled>{{ __('sms.landing.out_of_stock') }}</button>
                                @else
                                    <button type="button" class="btn btn-primary" style="flex:1;" data-buy-product="{!! e(json_encode($buyPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) !!}">{{ __('sms.landing.buy_now') }}</button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="empty" id="product-empty" style="display:none;margin-top:12px;background:rgba(255,255,255,0.02);border:1px solid var(--border-light);padding:3rem;text-align:center;border-radius:var(--radius-md);">{{ __('sms.landing.no_product_results') }}</div>
            @endif
        </div>
    </div>
</section>

<form method="post" action="{{ route('sms.order.create') }}" id="checkout-form">
    @csrf
    <input type="hidden" name="payment_method" value="balance">
    <input type="hidden" name="service_code" id="input_service_code">
    <input type="hidden" name="country_code" id="input_country_code">
    <input type="hidden" name="quantity" id="input_quantity">
    <input type="hidden" name="displayed_price" id="input_displayed_price">
    
    <div class="confirm-modal" id="confirm-modal" aria-hidden="true">
        <div class="modal-card" role="dialog" aria-modal="true">
            <h3 style="font-size:24px;margin-bottom:8px">{{ __('sms.landing.modal_order_title') }}</h3>
            <p class="muted" style="line-height:1.7;margin:0 0 20px">{{ __('sms.landing.modal_order_sub') }}</p>
            
            <div class="mini-summary" style="background:rgba(0,0,0,0.3); border:1px solid var(--border-light); border-radius:var(--radius-md); padding:1rem; margin-bottom: 20px;">
                <div class="summary-row" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px; margin-bottom:12px;">
                    <span class="muted">{{ __('sms.landing.product_name') }}</span><b id="modal-title" style="color:white;font-size:16px;">--</b>
                </div>
                <div class="summary-row" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px; margin-bottom:12px;">
                    <span class="muted">{{ __('sms.landing.unit_price') }}</span><b id="modal-price" style="color:var(--accent-cyan)">¥--</b>
                </div>
                <div class="summary-row" style="border-bottom:1px solid rgba(255,255,255,0.05); padding-bottom:12px; margin-bottom:12px;">
                    <span class="muted">{{ __('sms.landing.quantity') }}</span><b id="modal-qty" style="color:white;font-size:16px;">--</b>
                </div>
                <div class="summary-row" style="padding-top:6px;">
                    <span class="muted" style="font-size:16px; font-weight:bold;">{{ __('sms.landing.total_pay') }}</span>
                    <strong id="modal-total" style="font-size:28px; color:var(--accent-cyan); line-height:1;">¥--</strong>
                </div>
            </div>
            
            @auth
                <div id="balance-warning" style="display:none;background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); padding:1rem; border-radius:var(--radius-sm); margin-bottom:20px;">
                    <p style="margin:0; color:#fcd34d;"></p>
                </div>
                <div class="modal-actions" style="display:flex; gap:12px; margin-top:24px;">
                    <button type="button" class="btn btn-dark" style="flex:1;" onclick="closeModal()">{{ __('sms.landing.cancel') }}</button>
                    <button id="confirm-pay-button" class="btn btn-primary" type="submit" style="flex:2;font-size:16px;">{{ __('sms.landing.confirm_pay') }}</button>
                    <a id="modal-topup-link" href="{{ route('sms.recharge.index') }}" class="btn btn-white" style="flex:2;display:none;">{{ __('sms.landing.top_up_now') }}</a>
                </div>
            @else
                <div style="background:rgba(245, 158, 11, 0.1); border:1px solid rgba(245, 158, 11, 0.3); padding:1rem; border-radius:var(--radius-sm); margin-bottom:20px;">
                    <p style="margin:0; color:#fcd34d;">{{ __('sms.landing.login_required_purchase') }}</p>
                </div>
                <div class="modal-actions" style="display:flex; gap:12px; margin-top:24px;">
                    <button type="button" class="btn btn-dark" style="flex:1;" onclick="closeModal()">{{ __('sms.landing.cancel') }}</button>
                    <a href="{{ route('login') }}" class="btn btn-primary" style="flex:2;">{{ __('sms.landing.login_account') }}</a>
                </div>
            @endauth
        </div>
    </div>
</form>

@endsection
@section('scripts')
@php
    $landingI18n = [
        'quantityUnit' => __('sms.landing.quantity_unit'),
        'balanceInsufficient' => __('sms.landing.balance_insufficient', ['balance' => number_format($walletBalance, 2)]),
    ];
@endphp
<script>
const walletBalance = @json($walletBalance);
const landingI18n = @json($landingI18n);

function qtyChange(inputId, delta, max) {
    const el = document.getElementById(inputId);
    let val = parseInt(el.value) || 1;
    val += delta;
    if (val < 1) val = 1;
    if (val > max) val = max;
    el.value = val;
}

const modal = document.getElementById('confirm-modal');

function openModal(data) {
    const qty = parseInt(document.getElementById(data.qtyId).value) || 1;
    const total = (data.price * qty).toFixed(2);
    
    document.getElementById('input_service_code').value = data.service_code;
    document.getElementById('input_country_code').value = data.country_code;
    document.getElementById('input_quantity').value = qty;
    document.getElementById('input_displayed_price').value = Number(data.price).toFixed(2);
    
    document.getElementById('modal-title').textContent = data.title;
    document.getElementById('modal-price').textContent = '¥' + Number(data.price).toFixed(2);
    document.getElementById('modal-qty').textContent = landingI18n.quantityUnit.replace(':count', qty);
    document.getElementById('modal-total').textContent = '¥' + total;

    const warning = document.getElementById('balance-warning');
    const payButton = document.getElementById('confirm-pay-button');
    const topupLink = document.getElementById('modal-topup-link');
    if (warning && payButton && topupLink) {
        const insufficient = Number(walletBalance) + 0.00001 < Number(total);
        warning.style.display = insufficient ? '' : 'none';
        warning.querySelector('p').textContent = landingI18n.balanceInsufficient;
        payButton.style.display = insufficient ? 'none' : '';
        payButton.disabled = insufficient;
        topupLink.style.display = insufficient ? '' : 'none';
    }
    
    modal.classList.add('open');
}

function closeModal() {
    modal.classList.remove('open');
}

if(modal) {
    modal.addEventListener('click', e => {
        if(e.target === modal) closeModal();
    });
}

document.querySelectorAll('[data-buy-product]').forEach(button => {
    button.addEventListener('click', () => {
        try {
            openModal(JSON.parse(button.dataset.buyProduct || '{}'));
        } catch (e) {
            console.error(e);
        }
    });
});

const search = document.getElementById('product-search');
const empty = document.getElementById('product-empty');
if(search) {
    search.addEventListener('input', function() {
        const q = this.value.trim().toLowerCase();
        let shown = 0;
        document.querySelectorAll('.product-card-enhanced').forEach(card => {
            const ok = !q || (card.dataset.search || '').toLowerCase().includes(q);
            card.style.display = ok ? '' : 'none';
            if (ok) shown++;
        });
        if (empty) empty.style.display = shown ? 'none' : '';
    });
}
</script>
@endsection
