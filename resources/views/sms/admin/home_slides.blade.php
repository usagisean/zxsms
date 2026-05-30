@extends('sms.admin.layout')
@section('title','首页轮播')
@section('subtitle','轮播图片全语言共用，字幕按语言单独编辑；增加语言时会自动出现新的编辑区。')
@section('content')
@php
    $localeOptions = collect(config('sms.locale.supported'))->map(function ($meta, $code) {
        return [
            'code' => $code,
            'label' => is_array($meta) ? ($meta['label'] ?? $code) : $meta,
            'native' => is_array($meta) ? ($meta['native'] ?? ($meta['label'] ?? $code)) : $meta,
            'flag' => is_array($meta) ? ($meta['flag'] ?? '') : '',
        ];
    })->values();
    $defaultLocale = config('sms.locale.default', 'zh_CN');
    $fields = [
        'badge' => ['label' => '角标', 'type' => 'input', 'placeholder' => '长效接码号'],
        'title' => ['label' => '标题', 'type' => 'input', 'placeholder' => '60 天长效接码'],
        'description' => ['label' => '描述', 'type' => 'textarea', 'placeholder' => '展示在主标题下方的一段介绍'],
        'card_title' => ['label' => '卡片标题', 'type' => 'input', 'placeholder' => '号码有效期'],
        'card_value' => ['label' => '卡片大字', 'type' => 'input', 'placeholder' => '60 天'],
        'card_description' => ['label' => '卡片描述', 'type' => 'input', 'placeholder' => '适合长期账号验证'],
    ];
    $copyValue = function ($slide, $locale, $field) use ($defaultLocale) {
        $translations = is_array($slide->translations ?? null) ? $slide->translations : [];
        if (isset($translations[$locale][$field])) {
            return $translations[$locale][$field];
        }
        if ($locale === $defaultLocale) {
            return $slide->{$field} ?? '';
        }
        return '';
    };
@endphp

<div class="card">
    <div class="section-title"><h2>新增首页轮播</h2><span class="badge">Multi-language</span></div>
    <p class="help">图片地址填 <code>/images/home/slide-1.webp</code> 或完整 URL。图片里不建议写字，字幕在这里分语言编辑。</p>
    <form method="post" action="{{ route('sms.admin.home-slides.create') }}">
        @csrf
        <div class="grid3" style="margin-bottom:14px">
            <div class="form-row" style="grid-column:span 2"><label>图片地址</label><input name="image_url" placeholder="/images/home/slide-1.webp"></div>
            <div class="form-row"><label>排序</label><input name="sort_order" value="0"></div>
        </div>
        <div class="locale-slide-grid">
            @foreach($localeOptions as $locale)
                <div class="subcard locale-copy-card">
                    <div class="section-title">
                        <h2 style="font-size:18px">{{ $locale['flag'] }} {{ $locale['native'] }}</h2>
                        @if($locale['code'] === $defaultLocale)<span class="badge">默认语言</span>@else<span class="badge">{{ $locale['code'] }}</span>@endif
                    </div>
                    <div class="grid">
                        @foreach($fields as $field => $meta)
                            <div class="form-row" @if(in_array($field, ['title','description'], true)) style="grid-column:1/-1" @endif>
                                <label>{{ $meta['label'] }} @if($field === 'title' && $locale['code'] === $defaultLocale)<span class="muted">必填</span>@endif</label>
                                @if($meta['type'] === 'textarea')
                                    <textarea name="translations[{{ $locale['code'] }}][{{ $field }}]" rows="2" placeholder="{{ $meta['placeholder'] }}"></textarea>
                                @else
                                    <input name="translations[{{ $locale['code'] }}][{{ $field }}]" @if($field === 'title' && $locale['code'] === $defaultLocale) required @endif placeholder="{{ $meta['placeholder'] }}">
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px">
            <label class="check"><input type="checkbox" name="is_enabled" value="1" checked> 启用</label>
            <button>新增轮播</button>
        </div>
    </form>
</div>

<div class="card">
    <div class="section-title"><h2>首页轮播</h2><span class="badge">{{ $slides->total() }} 条</span></div>
    @forelse($slides as $slide)
        <form method="post" action="{{ route('sms.admin.home-slides.save',$slide) }}" class="subcard" style="margin-bottom:14px">
            @csrf
            <div class="grid3" style="margin-bottom:14px">
                <div class="form-row"><label>ID</label><input value="{{ $slide->id }}" disabled></div>
                <div class="form-row"><label>排序</label><input name="sort_order" value="{{ $slide->sort_order }}" placeholder="排序"></div>
                <div class="form-row"><label>图片地址</label><input name="image_url" value="{{ $slide->image_url }}" placeholder="/images/home/slide-1.webp"></div>
            </div>
            <div class="locale-slide-grid">
                @foreach($localeOptions as $locale)
                    <div class="subcard locale-copy-card">
                        <div class="section-title">
                            <h2 style="font-size:18px">{{ $locale['flag'] }} {{ $locale['native'] }}</h2>
                            @if($locale['code'] === $defaultLocale)<span class="badge">默认语言</span>@else<span class="badge">{{ $locale['code'] }}</span>@endif
                        </div>
                        <div class="grid">
                            @foreach($fields as $field => $meta)
                                <div class="form-row" @if(in_array($field, ['title','description'], true)) style="grid-column:1/-1" @endif>
                                    <label>{{ $meta['label'] }}</label>
                                    @if($meta['type'] === 'textarea')
                                        <textarea name="translations[{{ $locale['code'] }}][{{ $field }}]" rows="2" placeholder="{{ $meta['placeholder'] }}">{{ $copyValue($slide, $locale['code'], $field) }}</textarea>
                                    @else
                                        <input name="translations[{{ $locale['code'] }}][{{ $field }}]" value="{{ $copyValue($slide, $locale['code'], $field) }}" @if($field === 'title' && $locale['code'] === $defaultLocale) required @endif placeholder="{{ $meta['placeholder'] }}">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:14px">
                <label class="check"><input type="checkbox" name="is_enabled" value="1" @if($slide->is_enabled) checked @endif> 启用</label>
                <button class="small">保存轮播</button>
            </div>
        </form>
    @empty
        <div class="empty">暂无轮播，请先新增。</div>
    @endforelse
    {{ $slides->links() }}
</div>

<style>
.locale-slide-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.locale-copy-card{margin:0;background:rgba(255,255,255,.025)}
@media(max-width:980px){.locale-slide-grid{grid-template-columns:1fr}}
</style>
@endsection
