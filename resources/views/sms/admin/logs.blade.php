@extends('sms.admin.layout')
@section('title','API 日志')
@section('content')
<div class="card"><h1>HeroSMS API 日志</h1><form class="grid3"><input name="action" value="{{ request('action') }}" placeholder="action"><select name="success"><option value="">全部</option><option value="1" @if(request('success')==='1') selected @endif>成功</option><option value="0" @if(request('success')==='0') selected @endif>失败</option></select><button>筛选</button></form></div>
<div class="card"><table class="table"><thead><tr><th>时间</th><th>Action</th><th>成功</th><th>耗时</th><th>请求</th><th>响应</th></tr></thead><tbody>@foreach($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->action }}<br><span class="small">HTTP {{ $log->http_status }}</span></td><td>{{ $log->is_success ? '是':'否' }} @if($log->error_message)<br><span class="danger small">{{ $log->error_message }}</span>@endif</td><td>{{ $log->duration_ms }}ms</td><td><pre class="small">{{ json_encode($log->request_payload, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></td><td><pre class="small">{{ mb_substr($log->response_body,0,800) }}</pre></td></tr>@endforeach</tbody></table><div>{{ $logs->links() }}</div></div>
@endsection
