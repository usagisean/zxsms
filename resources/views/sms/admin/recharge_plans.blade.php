@extends('sms.admin.layout')
@section('title','充值档位')
@section('subtitle','用户先充值余额，再从余额扣款购买号码；无码/失败自动退回余额。')
@section('content')
<div class="card">
    <div class="section-title"><h2>新增充值档位</h2><span class="badge">Plan</span></div>
    <form method="post" action="{{ route('sms.admin.recharge-plans.create') }}" class="grid3">@csrf
        <div class="form-row"><label>名称</label><input name="name" placeholder="常用包"></div>
        <div class="form-row"><label>支付金额</label><input name="amount" placeholder="50"></div>
        <div class="form-row"><label>赠送金额</label><input name="bonus_amount" placeholder="2"></div>
        <div class="form-row"><label>标签</label><input name="badge" placeholder="推荐"></div>
        <div class="form-row"><label>排序</label><input name="sort_order" value="0"></div>
        <label class="check" style="align-self:end"><input type="checkbox" name="is_enabled" value="1" checked> 启用</label>
        <div style="align-self:end"><button>新增</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table">
        <thead><tr><th style="width:90px">ID</th><th>名称</th><th>金额</th><th>赠送/排序</th><th>状态</th><th style="width:110px">保存</th></tr></thead>
        <tbody>@foreach($plans as $p)
            @php($formId = 'plan-form-' . $p->id)
            <tr>
                <td class="mono">{{ $p->id }}</td>
                <td><input form="{{ $formId }}" name="name" value="{{ $p->name }}"><input form="{{ $formId }}" name="badge" value="{{ $p->badge }}" placeholder="标签" style="margin-top:10px"></td>
                <td><input form="{{ $formId }}" name="amount" value="{{ $p->amount }}"></td>
                <td><input form="{{ $formId }}" name="bonus_amount" value="{{ $p->bonus_amount }}"><input form="{{ $formId }}" name="sort_order" value="{{ $p->sort_order }}" placeholder="排序" style="margin-top:10px"></td>
                <td><label class="check"><input form="{{ $formId }}" type="checkbox" name="is_enabled" value="1" @if($p->is_enabled) checked @endif> 启用</label></td>
                <td><form id="{{ $formId }}" method="post" action="{{ route('sms.admin.recharge-plans.save',$p) }}">@csrf</form><button form="{{ $formId }}" class="small">保存</button></td>
            </tr>
        @endforeach</tbody>
    </table></div>
    {{ $plans->links() }}
</div>
@endsection
