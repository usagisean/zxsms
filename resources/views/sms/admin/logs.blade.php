@extends('sms.admin.layout')
@section('title','API 日志')
@section('subtitle','上游/库存取码请求日志，用于排查同步价格、发货和查码问题。')
@section('content')
<div class="card">
    <form class="grid3">
        <div class="form-row"><label>Action</label><input name="action" value="{{ request('action') }}" placeholder="getPrices / getNumberV2"></div>
        <div class="form-row"><label>结果</label><select name="success"><option value="">全部</option><option value="1" @if(request('success')==='1') selected @endif>成功</option><option value="0" @if(request('success')==='0') selected @endif>失败</option></select></div>
        <div style="align-self:end"><button>筛选</button></div>
    </form>
</div>
<div class="card">
    <div class="table-wrap"><table class="table" style="min-width:1120px">
        <thead><tr><th>时间</th><th>Action</th><th>成功</th><th>耗时</th><th>请求</th><th>响应</th></tr></thead>
        <tbody>@forelse($logs as $log)<tr>
            <td>{{ $log->created_at }}</td><td><b>{{ $log->action }}</b><br><span class="small muted">HTTP {{ $log->http_status }}</span></td>
            <td>{{ $log->is_success ? '是':'否' }} @if($log->error_message)<br><span class="danger small">{{ $log->error_message }}</span>@endif</td><td>{{ $log->duration_ms }}ms</td>
            <td><pre class="small">{{ json_encode($log->request_payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td><td><pre class="small">{{ mb_substr($log->response_body,0,1000) }}</pre></td>
        </tr>@empty<tr><td colspan="6" class="empty">暂无 API 日志。</td></tr>@endforelse</tbody>
    </table></div>
    {{ $logs->links() }}
</div>
@endsection
