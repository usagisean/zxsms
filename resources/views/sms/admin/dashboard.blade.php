@extends('sms.admin.layout')
@section('title','HeroSMS 后台')
@section('content')
<div class="card"><h1>HeroSMS 接码后台</h1><p class="muted">独立站后台，不影响原发卡网订单。</p></div>
<div class="grid3">
@foreach($counts as $k=>$v)<div class="card"><div class="muted">{{ $k }}</div><h2>{{ $v }}</h2></div>@endforeach
</div>
@endsection
