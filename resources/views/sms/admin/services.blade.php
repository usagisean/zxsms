@extends('sms.admin.layout')
@section('title','服务管理')
@section('subtitle','服务来自 HeroSMS 同步；可控制前台展示、推荐排序，也可对单个服务覆盖统一定价。')
@section('content')
<div class="card">
    <form class="grid3"><div class="form-row" style="grid-column:1 / span 2"><label>搜索</label><input name="q" value="{{ request('q') }}" placeholder="搜索名称或 code"></div><div style="align-self:end"><button>筛选</button></div></form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th style="width:90px">ID</th><th style="width:260px">服务</th><th>状态、排序与覆盖价格规则</th><th style="width:90px">价格数</th><th style="width:110px">操作</th></tr></thead>
        <tbody>@foreach($services as $s)
            @php($formId = 'service-form-' . $s->id)
            <tr>
                <td class="mono">{{ $s->id }}</td>
                <td><b>{{ $s->name }}</b><br><span class="mono muted">{{ $s->provider_code }}</span></td>
                <td>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center"><label class="check"><input form="{{ $formId }}" type="checkbox" name="is_enabled" value="1" @if($s->is_enabled) checked @endif> 启用</label><label class="check"><input form="{{ $formId }}" type="checkbox" name="is_featured" value="1" @if($s->is_featured) checked @endif> 前台推荐</label></div>
                    <div class="rule-grid">
                        <input form="{{ $formId }}" name="sort_order" value="{{ $s->sort_order }}" placeholder="排序，越小越前">
                        <input form="{{ $formId }}" name="markup_multiplier" value="{{ $s->markup_multiplier }}" placeholder="覆盖加价倍数">
                        <input form="{{ $formId }}" name="fixed_fee" value="{{ $s->fixed_fee }}" placeholder="覆盖固定费">
                        <input form="{{ $formId }}" name="min_profit" value="{{ $s->min_profit }}" placeholder="覆盖最低利润">
                        <input form="{{ $formId }}" name="min_price" value="{{ $s->min_price }}" placeholder="覆盖最低售价">
                    </div>
                </td>
                <td>{{ $s->prices_count }}</td>
                <td><form id="{{ $formId }}" method="post" action="{{ route('sms.admin.services.save',$s) }}">@csrf</form><button form="{{ $formId }}" class="small">保存</button></td>
            </tr>
        @endforeach</tbody>
    </table></div>
    {{ $services->links() }}
</div>
@endsection
