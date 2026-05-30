@extends('sms.layouts.site')
@section('body_class', 'home-page')
@section('title', __('sms.home.title'))
@section('content')
<section class="home-hero">
    <div class="container">
        <div class="home-carousel carousel" data-carousel>
            <div class="home-admin-note">{{ __('sms.home.admin_note') }}</div>
            @foreach($slides as $slide)
                <div class="slide @if($loop->first) active @endif">
                    <div class="home-slide" style="--slide-image:url('{{ $slide->image_url ?: asset('images/home/slide-'.$loop->iteration.'.webp') }}')">
                        <div class="home-slide-content">
                            <div class="eyebrow">{{ $slide->badge }}</div>
                            <h1>{{ $slide->title }}</h1>
                            <p>{{ $slide->description }}</p>
                            <div class="hero-actions">
                                <a class="btn btn-primary" href="{{ route('sms.index') }}">{{ __('sms.home.cta_get') }}</a>
                                <a class="btn btn-white" href="{{ route('sms.recharge.index') }}">{{ __('sms.home.cta_recharge') }}</a>
                                <a class="btn btn-ghost" href="{{ route('sms.query') }}">{{ __('sms.home.cta_query') }}</a>
                            </div>
                            <div class="home-metrics">
                                <div class="home-metric"><b>{{ __('sms.home.metric_balance') }}</b><span>{{ __('sms.home.metric_balance_desc') }}</span></div>
                                <div class="home-metric"><b>{{ __('sms.home.metric_live') }}</b><span>{{ __('sms.home.metric_live_desc') }}</span></div>
                                <div class="home-metric"><b>{{ __('sms.home.metric_auto') }}</b><span>{{ __('sms.home.metric_auto_desc') }}</span></div>
                            </div>
                        </div>
                        <div class="home-slide-card">
                            <div class="muted">{{ $slide->card_title }}</div>
                            <div class="price">{{ $slide->card_value }}</div>
                            <h3 style="font-size:26px;margin:10px 0 6px">{{ $slide->card_description }}</h3>
                            <p class="muted" style="margin:0;line-height:1.7">{{ __('sms.home.card_note') }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="carousel-dots dots" data-carousel-dots>
                @foreach($slides as $slide)<button class="carousel-dot dot @if($loop->first) active @endif" type="button"></button>@endforeach
            </div>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
(function(){
    var root=document.querySelector('[data-carousel]'); if(!root) return;
    var slides=[].slice.call(root.querySelectorAll('.slide'));
    var dots=[].slice.call(root.querySelectorAll('.dot'));
    var i=0;
    function show(n){i=n;slides.forEach(function(s,idx){s.classList.toggle('active',idx===i)});dots.forEach(function(d,idx){d.classList.toggle('active',idx===i)});}
    dots.forEach(function(d,idx){d.addEventListener('click',function(){show(idx)});});
    setInterval(function(){show((i+1)%slides.length)},5000);
})();
</script>
@endsection
