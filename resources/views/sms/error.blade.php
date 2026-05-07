@extends('sms.layouts.site')
@section('title', __('sms.error.title'))
@section('content')
<section class="auth-shell">
    <div class="panel auth-card">
        <div class="eyebrow">{{ __('sms.error.eyebrow') }}</div>
        <h1 style="font-size:38px;margin:0 0 16px">{{ __('sms.error.headline') }}</h1>
        <div class="err" style="width:100%;margin:0 0 22px">{{ $message }}</div>
        <a class="btn btn-primary" href="{{ route('sms.index') }}">{{ __('sms.error.back') }}</a>
    </div>
</section>
@endsection
