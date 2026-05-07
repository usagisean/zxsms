@extends('sms.admin.layout')
@section('title','国家管理')
@section('subtitle','默认使用统一价格规则；只有某个国家需要特殊利润时，才在这里填写覆盖规则。')
@section('content')
<div class="card">
    <form class="grid3"><div class="form-row" style="grid-column:1 / span 2"><label>搜索</label><input name="q" value="{{ request('q') }}" placeholder="搜索国家或 provider id"></div><div style="align-self:end"><button>筛选</button></div></form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th style="width:90px">ID</th><th style="width:230px">国家</th><th>状态与覆盖价格规则</th><th style="width:90px">价格数</th><th style="width:110px">操作</th></tr></thead>
        <tbody>@foreach($countries as $c)
            @php($formId = 'country-form-' . $c->id)
            <tr>
                <td class="mono">{{ $c->provider_id }}</td>
                <td><b>{{ $c->name }}</b><br><span class="muted">{{ $c->name_en }}</span></td>
                <td>
                    <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center"><label class="check"><input form="{{ $formId }}" type="checkbox" name="is_enabled" value="1" @if($c->is_enabled) checked @endif> 启用</label><span class="badge">HeroSMS可见：{{ $c->provider_visible ? '是':'否' }}</span></div>
                    <div class="rule-grid">
                        <input form="{{ $formId }}" name="markup_multiplier" value="{{ $c->markup_multiplier }}" placeholder="覆盖加价倍数">
                        <input form="{{ $formId }}" name="fixed_fee" value="{{ $c->fixed_fee }}" placeholder="覆盖固定费">
                        <input form="{{ $formId }}" name="min_profit" value="{{ $c->min_profit }}" placeholder="覆盖最低利润">
                        <input form="{{ $formId }}" name="min_price" value="{{ $c->min_price }}" placeholder="覆盖最低售价">
                    </div>
                </td>
                <td>{{ $c->prices_count }}</td>
                <td><form id="{{ $formId }}" method="post" action="{{ route('sms.admin.countries.save',$c) }}">@csrf</form><button form="{{ $formId }}" class="small">保存</button></td>
            </tr>
        @endforeach</tbody>
    </table></div>
    {{ $countries->links() }}
</div>
@endsection
