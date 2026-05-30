@php
    $smsSettings = app(\App\Services\Sms\SmsSettingService::class);
    $siteName = $smsSettings->get('site_name', __('sms.brand'));
    $siteDomain = $smsSettings->get('site_domain', __('sms.domain'));
    $siteFooterDesc = $smsSettings->get('site_footer_desc', __('sms.footer.desc'));
    $supportTgUrl = $smsSettings->get('support_tg_url', '');
    $supportTgLabel = $smsSettings->get('support_tg_label', __('sms.landing.support_tg'));
    $communityTgUrl = $smsSettings->get('community_tg_url', '');
    $communityTgLabel = $smsSettings->get('community_tg_label', __('sms.landing.community_tg'));
    $productValidityDays = (int) $smsSettings->get('product_validity_days', 60);
    $productMinValidityDays = (int) $smsSettings->get('product_min_validity_days', 30);
    $productLongTermNote = $smsSettings->get('product_long_term_note', __('sms.landing.long_term_default'));
    $assetVersion = @filemtime(public_path('css/app.css')) ?: '1';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', __('sms.home.title'))</title>
    <meta name="description" content="{{ __('sms.meta_description') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ $assetVersion }}">
</head>
<body class="@yield('body_class')">
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="{{ route('home') }}" aria-label="{{ $siteName }}">
            <span class="brand-mark"><span>✦</span></span>
            <span class="brand-name">{{ $siteName }}</span>
        </a>
        <nav class="nav" aria-label="{{ __('sms.common.main_nav') }}">
            <a href="{{ route('sms.index') }}" class="{{ request()->routeIs('sms.index') ? 'active' : '' }}">{{ __('sms.nav.get_number') }}</a>
            <a href="{{ route('sms.account.numbers') }}" class="{{ request()->routeIs('sms.account.numbers') ? 'active' : '' }}">{{ __('sms.nav.my_numbers') }}</a>
            <a href="{{ route('sms.recharge.index') }}" class="{{ request()->routeIs('sms.recharge.*') ? 'active' : '' }}">{{ __('sms.nav.recharge') }}</a>
            <a href="{{ route('sms.query') }}" class="{{ request()->routeIs('sms.query') ? 'active' : '' }}">{{ __('sms.nav.query') }}</a>
        </nav>
        <div class="userbar">
            <form class="lang-switch" method="get" action="{{ url()->current() }}">
                @foreach(request()->except('lang') as $key => $value)
                    @if(is_scalar($value))<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif
                @endforeach
                <select name="lang" aria-label="{{ __('sms.common.language') }}" onchange="this.form.submit()">
                    @foreach(config('sms.locale.supported') as $code => $label)
                        <option value="{{ $code }}" @if(app()->getLocale()===$code) selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
            @auth
                @php($navWallet = app(\App\Services\Sms\SmsWalletService::class)->wallet(auth()->user()))
                <span class="avatar">{{ mb_substr(auth()->user()->name ?: auth()->user()->email, 0, 1) }}</span>
                <span class="user-meta">{{ auth()->user()->name ?: __('sms.common.user') }}<small>{{ __('sms.common.balance') }} ¥{{ number_format((float)$navWallet->balance, 2) }}</small></span>
                <a class="btn btn-primary" href="{{ route('sms.recharge.index') }}">{{ __('sms.nav.recharge') }}</a>
                <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-dark" type="submit">{{ __('sms.nav.logout') }}</button></form>
            @else
                <a class="btn btn-ghost" href="{{ route('login') }}">{{ __('sms.nav.login') }}</a>
                <a class="btn btn-primary" href="{{ route('register') }}">{{ __('sms.nav.register') }}</a>
            @endauth
        </div>
        <button class="menu-btn" type="button" data-menu-toggle aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
    <div class="container mobile-menu" data-mobile-menu>
        <div class="mobile-langs">
            @foreach(config('sms.locale.supported') as $code => $label)
                <a href="{{ request()->fullUrlWithQuery(['lang'=>$code]) }}">{{ $label }}</a>
            @endforeach
        </div>
        <a href="{{ route('sms.index') }}">{{ __('sms.nav.get_number') }}</a>
        <a href="{{ route('sms.account.numbers') }}">{{ __('sms.nav.my_numbers') }}</a>
        <a href="{{ route('sms.recharge.index') }}">{{ __('sms.nav.recharge') }}</a>
        <a href="{{ route('sms.query') }}">{{ __('sms.nav.query') }}</a>
        @if($supportTgUrl)
            <a href="{{ $supportTgUrl }}" target="_blank" rel="noopener">{{ $supportTgLabel }}</a>
        @endif
        @if($communityTgUrl)
            <a href="{{ $communityTgUrl }}" target="_blank" rel="noopener">{{ $communityTgLabel }}</a>
        @endif
        @auth
            <form method="post" action="{{ route('logout') }}">@csrf<button type="submit">{{ __('sms.nav.logout') }}：{{ auth()->user()->email }}</button></form>
        @else
            <a href="{{ route('login') }}">{{ __('sms.nav.email_login') }}</a>
            <a href="{{ route('register') }}">{{ __('sms.nav.register') }}</a>
        @endauth
    </div>
</header>

@if(session('ok'))<div class="ok">{{ session('ok') }}</div>@endif
@if(session('quote_changed'))<div class="err">{{ session('quote_changed') }} · {{ __('sms.common.new_price') }}：¥{{ session('new_price') }}</div>@endif
@if($errors->any())<div class="err">{{ $errors->first() }}</div>@endif

<main>
    @yield('content')
</main>

@if($supportTgUrl)
    <a class="chat-float" href="{{ $supportTgUrl }}" target="_blank" rel="noopener" title="{{ $supportTgLabel }}">✈</a>
@else
    <a class="chat-float" href="{{ route('sms.query') }}" title="{{ __('sms.nav.query') }}">☏</a>
@endif
<footer class="footer-enhanced">
    <div class="container footer-inner">
        <div class="footer-grid">
            <div class="footer-brand">
                <a class="brand" href="{{ route('home') }}" aria-label="{{ $siteName }}">
                    <span class="brand-mark"><span>✦</span></span>
                    <span class="brand-name">{{ $siteName }}</span>
                </a>
                <p class="footer-desc">{{ $siteDomain }} · {{ $siteFooterDesc }}</p>
                <p class="footer-note">{{ $productLongTermNote }}</p>
            </div>
            <div class="footer-links">
                <div class="link-col">
                    <h4>{{ __('sms.common.main_nav') }}</h4>
                    <a href="{{ route('sms.index') }}">{{ __('sms.nav.get_number') }}</a>
                    <a href="{{ route('sms.account.numbers') }}">{{ __('sms.nav.my_numbers') }}</a>
                    <a href="{{ route('sms.recharge.index') }}">{{ __('sms.nav.recharge') }}</a>
                    <a href="{{ route('sms.query') }}">{{ __('sms.nav.query') }}</a>
                </div>
                <div class="link-col">
                    <h4>{{ __('sms.footer.support_community') }}</h4>
                    @if($supportTgUrl)<a href="{{ $supportTgUrl }}" target="_blank" rel="noopener">{{ $supportTgLabel }}</a>@endif
                    @if($communityTgUrl)<a href="{{ $communityTgUrl }}" target="_blank" rel="noopener">{{ $communityTgLabel }}</a>@endif
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} {{ $siteDomain }}. All rights reserved.</p>
        </div>
    </div>
</footer>
<script>
(function(){
    var btn=document.querySelector('[data-menu-toggle]');
    var menu=document.querySelector('[data-mobile-menu]');
    function setMenu(open){
        if(!btn||!menu) return;
        btn.classList.toggle('is-open', open);
        menu.classList.toggle('open', open);
        document.body.classList.toggle('menu-open', open);
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    }
    if(btn&&menu){
        btn.setAttribute('aria-expanded','false');
        btn.addEventListener('click',function(){setMenu(!menu.classList.contains('open'));});
        menu.querySelectorAll('a,button').forEach(function(item){item.addEventListener('click',function(){setMenu(false);});});
        document.addEventListener('keydown',function(e){if(e.key==='Escape') setMenu(false);});
        window.addEventListener('resize',function(){if(window.innerWidth>1020) setMenu(false);});
    }
    var targets=[].slice.call(document.querySelectorAll('.panel,.feature-card,.step-card,.faq-card,.service-item,.pay-card,.stat,.trust-card,.feature-tile,.market-panel,.checkout-card'));
    if('IntersectionObserver' in window && !window.matchMedia('(prefers-reduced-motion: reduce)').matches){
        var io=new IntersectionObserver(function(entries){entries.forEach(function(entry){if(entry.isIntersecting){entry.target.classList.add('is-visible');io.unobserve(entry.target);}});},{threshold:.12,rootMargin:'0px 0px -8% 0px'});
        targets.forEach(function(el,idx){el.classList.add('reveal-ready');el.style.transitionDelay=Math.min(idx%6*55,280)+'ms';io.observe(el);});
        setTimeout(function(){targets.forEach(function(el){el.classList.add('is-visible');});}, 140);
    }else{targets.forEach(function(el){el.classList.add('is-visible');});}
})();
</script>
@yield('scripts')
</body>
</html>
