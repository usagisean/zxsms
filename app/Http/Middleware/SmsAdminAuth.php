<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SmsAdminAuth
{
    public function handle(Request $request, Closure $next)
    {
        $user = (string) config('sms.admin.username', 'admin');
        $password = (string) config('sms.admin.password');

        if ($password === '') {
            return response('请先在 .env 配置 SMS_ADMIN_PASSWORD', 500);
        }

        if ($request->getUser() !== $user || ! hash_equals($password, (string) $request->getPassword())) {
            return response('需要后台认证', 401, ['WWW-Authenticate' => 'Basic realm="HeroSMS Admin"']);
        }

        return $next($request);
    }
}
