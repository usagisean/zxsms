@extends('sms.layouts.site')
@section('body_class', 'home-page')
@section('title', 'ZXAIHUB SMS - zxaihub.com 在线接收验证码')
@section('content')
<section class="home-hero">
    <div class="container">
        <div class="home-carousel carousel" data-carousel>
            <div class="home-admin-note">首页图片后续可替换配置</div>
            @foreach($slides as $slide)
                <div class="slide @if($loop->first) active @endif">
                    <div class="home-slide" style="--slide-image:url('{{ $slide->image_url ?: asset('images/home/slide-'.$loop->iteration.'.jpg') }}')">
                        <div class="home-slide-content">
                            <div class="eyebrow">{{ $slide->badge }}</div>
                            <h1>{{ $slide->title }}</h1>
                            <p>{{ $slide->description }}</p>
                            <div class="hero-actions">
                                <a class="btn btn-primary" href="{{ route('sms.index') }}">获取号码</a>
                                <a class="btn btn-white" href="{{ route('sms.recharge.index') }}">充值余额</a>
                                <a class="btn btn-ghost" href="{{ route('sms.query') }}">订单查询</a>
                            </div>
                            <div class="home-metrics">
                                <div class="home-metric"><b>余额</b><span>充值后下单</span></div>
                                <div class="home-metric"><b>实时</b><span>确认成本</span></div>
                                <div class="home-metric"><b>自动</b><span>失败退余额</span></div>
                            </div>
                        </div>
                        <div class="home-slide-card">
                            <div class="muted">{{ $slide->card_title }}</div>
                            <div class="price">{{ $slide->card_value }}</div>
                            <h3 style="font-size:26px;margin:10px 0 6px">{{ $slide->card_description }}</h3>
                            <p class="muted" style="margin:0;line-height:1.7">后台继续保留充值档位、服务、国家、价格规则、订单和 API 日志管理。</p>
                        </div>
                    </div>
                </div>
            @endforeach
            <div class="dots" data-carousel-dots>
                @foreach($slides as $slide)<button class="dot @if($loop->first) active @endif" type="button"></button>@endforeach
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
