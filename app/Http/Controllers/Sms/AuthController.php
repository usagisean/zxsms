<?php

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('sms.auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');
        if (! Auth::attempt($credentials, $remember)) {
            return back()->withInput($request->only('email'))->withErrors(['email' => '邮箱或密码不正确']);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('sms.index'));
    }

    public function showRegister()
    {
        return view('sms.auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'] ?: strstr($data['email'], '@', true),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('sms.index')->with('ok', '注册成功，已登录。');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('ok', '已退出登录。');
    }
}
