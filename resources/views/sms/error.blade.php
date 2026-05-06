@extends('sms.layouts.site')
@section('title', '操作失败 - ZXAIHUB SMS')
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">⚠️ 操作失败</div>
        <h1 style="font-size:38px;margin:0 0 16px">暂时无法继续</h1>
        <div class="err" style="width:100%;margin:0 0 22px">{{ $message }}</div>
        <a class="btn btn-primary" href="{{ route('sms.index') }}">返回获取号码</a>
    </div>
</section>
@endsection
