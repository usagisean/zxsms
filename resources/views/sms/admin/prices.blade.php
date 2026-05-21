@extends('sms.admin.layout')
@section('title','前台商品设置')
@section('subtitle','自定义前台销售网格中每个商品的标题、简介、虚拟销量与限购数量。')
@section('content')
<div class="card">
    <div class="section-title"><h2>商品展示配置</h2><span class="badge">实时生效</span></div>
    <p class="help">“隐藏”会写入人工隐藏标记，后续刷新库存不会自动重新展示；需要恢复时重新勾选“显示”并保存。</p>
    <div class="table-wrap"><table class="table">
        <thead><tr><th style="min-width:140px;">商品 / 国家</th><th style="min-width:180px;">自定义标题</th><th style="min-width:240px;">简介说明</th><th style="min-width:80px;">基础销量</th><th style="min-width:80px;">单次限购</th><th>显示/保存</th></tr></thead>
        <tbody>@forelse($prices as $p)
        @php $formId = 'price-form-' . $p->id; @endphp
        <tr>
            <td><b>{{ $p->service->name ?? '-' }}</b><br><span class="muted">{{ $p->country->name ?? '-' }} (库存: {{ $p->stock_count }})</span><br><span class="muted">售价 ¥{{ $p->sale_price }}</span></td>
            <td><input form="{{ $formId }}" name="title" value="{{ $p->title }}" placeholder="如不填则默认"></td>
            <td><textarea form="{{ $formId }}" name="description" rows="2" placeholder="如不填则显示系统多语言默认描述" style="min-height:50px;">{{ $p->description }}</textarea></td>
            <td><input form="{{ $formId }}" name="base_sold_count" type="number" value="{{ $p->base_sold_count }}" min="0" style="width:80px;"></td>
            <td><input form="{{ $formId }}" name="max_quantity" type="number" value="{{ $p->max_quantity ?: 10 }}" min="1" max="50" style="width:80px;"></td>
            <td>
                <form id="{{ $formId }}" method="post" action="{{ route('sms.admin.prices.save', $p->id) }}">@csrf</form>
                <label class="check" style="margin-bottom:8px;"><input form="{{ $formId }}" type="checkbox" name="is_available" value="1" {{ $p->is_available ? 'checked' : '' }}> 显示</label><br>
                <button form="{{ $formId }}" type="submit" class="small">保存</button>
            </td>
        </tr>
        @empty<tr><td colspan="6" class="empty">暂无商品。请先在【号码库存】导入数据，它会自动生成这里的商品。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $prices->links() }}
</div>
@endsection
