@extends('sms.admin.layout')
@section('title','首页轮播')
@section('subtitle','首页只保留一个简洁轮播；图片地址可填 /images/home/slide-1.jpg 或完整 URL。')
@section('content')
<div class="card">
    <div class="section-title"><h2>新增首页轮播</h2><span class="badge">Homepage</span></div>
    <p class="help">如果你后面把图片上传到 <code>public/images/home/</code>，这里填 <code>/images/home/文件名.jpg</code> 即可。</p>
    <form method="post" action="{{ route('sms.admin.home-slides.create') }}" class="grid3">@csrf
        <div class="form-row"><label>角标</label><input name="badge" placeholder="ZXAIHUB SMS"></div>
        <div class="form-row"><label>标题</label><input name="title" required placeholder="充值余额，自动接收验证码"></div>
        <div class="form-row"><label>排序</label><input name="sort_order" value="0"></div>
        <div class="form-row" style="grid-column:1/-1"><label>描述</label><textarea name="description" rows="2"></textarea></div>
        <div class="form-row" style="grid-column:1/-1"><label>图片地址</label><input name="image_url" placeholder="/images/home/slide-1.jpg"></div>
        <div class="form-row"><label>卡片标题</label><input name="card_title"></div>
        <div class="form-row"><label>卡片大字</label><input name="card_value"></div>
        <div class="form-row"><label>卡片描述</label><input name="card_description"></div>
        <label class="check" style="align-self:end"><input type="checkbox" name="is_enabled" value="1" checked> 启用</label>
        <div style="align-self:end"><button>新增</button></div>
    </form>
</div>
<div class="card">
    <div class="section-title"><h2>首页轮播</h2><span class="badge">{{ $slides->total() }} 条</span></div>
    @forelse($slides as $slide)
        <form method="post" action="{{ route('sms.admin.home-slides.save',$slide) }}" class="subcard" style="margin-bottom:14px">@csrf
            <div class="grid3">
                <div class="form-row"><label>ID</label><input value="{{ $slide->id }}" disabled></div>
                <div class="form-row"><label>角标</label><input name="badge" value="{{ $slide->badge }}" placeholder="角标"></div>
                <div class="form-row"><label>排序</label><input name="sort_order" value="{{ $slide->sort_order }}" placeholder="排序"></div>
                <div class="form-row" style="grid-column:1/-1"><label>标题</label><input name="title" value="{{ $slide->title }}" required placeholder="标题"></div>
                <div class="form-row" style="grid-column:1/-1"><label>描述</label><textarea name="description" rows="3" placeholder="描述">{{ $slide->description }}</textarea></div>
                <div class="form-row" style="grid-column:1/-1"><label>图片地址</label><input name="image_url" value="{{ $slide->image_url }}" placeholder="图片地址"></div>
                <div class="form-row"><label>卡片标题</label><input name="card_title" value="{{ $slide->card_title }}" placeholder="卡片标题"></div>
                <div class="form-row"><label>卡片大字</label><input name="card_value" value="{{ $slide->card_value }}" placeholder="卡片大字"></div>
                <div class="form-row"><label>卡片描述</label><input name="card_description" value="{{ $slide->card_description }}" placeholder="卡片描述"></div>
                <label class="check" style="align-self:end"><input type="checkbox" name="is_enabled" value="1" @if($slide->is_enabled) checked @endif> 启用</label>
                <div style="align-self:end"><button class="small">保存</button></div>
            </div>
        </form>
    @empty
        <div class="empty">暂无轮播，请先新增。</div>
    @endforelse
    {{ $slides->links() }}
</div>
@endsection
