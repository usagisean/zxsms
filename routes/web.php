<?php

use App\Http\Controllers\Sms\Admin\SmsAdminController;
use App\Http\Controllers\Sms\AccountController;
use App\Http\Controllers\Sms\AuthController;
use App\Http\Controllers\Sms\HomeController;
use App\Http\Controllers\Sms\RechargeController;
use App\Http\Controllers\Sms\SmsController;
use App\Http\Controllers\Sms\SmsPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/login', [AuthController::class, 'showLogin'])->middleware('guest')->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest')->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->middleware('guest')->name('register');
Route::post('/register', [AuthController::class, 'register'])->middleware('guest')->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::get('/my-numbers', [AccountController::class, 'numbers'])->middleware('auth')->name('sms.account.numbers');
Route::get('/recharge', [RechargeController::class, 'index'])->name('sms.recharge.index');
Route::post('/recharge', [RechargeController::class, 'create'])->name('sms.recharge.create');
Route::get('/recharge/{token}', [RechargeController::class, 'show'])->name('sms.recharge.show');

Route::prefix('sms')->name('sms.')->group(function () {
    Route::get('/', [SmsController::class, 'index'])->name('index');
    Route::post('/order', [SmsController::class, 'createOrder'])->name('order.create');
    Route::get('/order/{token}', [SmsController::class, 'showOrder'])->name('order.show');
    Route::get('/order/{token}/status', [SmsController::class, 'orderStatus'])->name('order.status');
    Route::post('/order/{token}/cancel', [SmsController::class, 'cancelOrder'])->name('order.cancel');
    Route::get('/query', [SmsController::class, 'query'])->name('query');
    Route::post('/query', [SmsController::class, 'queryPost'])->name('query.post');

    Route::get('/pay-gateway/{methodCode}/{paymentSn}', [SmsPaymentController::class, 'gateway'])->name('pay.gateway');
    Route::get('/pay-recharge/{paymentSn}', [SmsPaymentController::class, 'rechargeGateway'])->name('pay.recharge.gateway');
    Route::match(['get', 'post'], '/pay/yipay/notify_url', [SmsPaymentController::class, 'yipayNotify'])->name('pay.yipay.notify');
    Route::get('/pay/yipay/return_url', [SmsPaymentController::class, 'yipayReturn'])->name('pay.yipay.return');
    Route::post('/pay/epusdt/notify_url', [SmsPaymentController::class, 'epusdtNotify'])->name('pay.epusdt.notify');
    Route::get('/pay/epusdt/return_url', [SmsPaymentController::class, 'epusdtReturn'])->name('pay.epusdt.return');
});

Route::prefix(env('ADMIN_PATH', 'sms-admin'))->name('sms.admin.')->middleware('sms.admin')->group(function () {
    Route::get('/', [SmsAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/settings', [SmsAdminController::class, 'settings'])->name('settings');
    Route::post('/settings', [SmsAdminController::class, 'saveSettings'])->name('settings.save');
    Route::get('/services', [SmsAdminController::class, 'services'])->name('services');
    Route::post('/services/{service}', [SmsAdminController::class, 'saveService'])->name('services.save');
    Route::get('/countries', [SmsAdminController::class, 'countries'])->name('countries');
    Route::post('/countries/{country}', [SmsAdminController::class, 'saveCountry'])->name('countries.save');
    Route::get('/prices', [SmsAdminController::class, 'prices'])->name('prices');
    Route::post('/prices/sync', [SmsAdminController::class, 'syncPrices'])->name('prices.sync');
    Route::post('/prices/{price}', [SmsAdminController::class, 'savePrice'])->whereNumber('price')->name('prices.save');
    Route::get('/inventory', [SmsAdminController::class, 'inventory'])->name('inventory');
    Route::post('/inventory/import', [SmsAdminController::class, 'importInventory'])->name('inventory.import');
    Route::post('/inventory/sync', [SmsAdminController::class, 'syncInventory'])->name('inventory.sync');
    Route::get('/home-slides', [SmsAdminController::class, 'homeSlides'])->name('home-slides');
    Route::post('/home-slides', [SmsAdminController::class, 'createHomeSlide'])->name('home-slides.create');
    Route::post('/home-slides/{slide}', [SmsAdminController::class, 'saveHomeSlide'])->name('home-slides.save');
    Route::get('/recharge-plans', [SmsAdminController::class, 'rechargePlans'])->name('recharge-plans');
    Route::post('/recharge-plans', [SmsAdminController::class, 'createRechargePlan'])->name('recharge-plans.create');
    Route::post('/recharge-plans/{plan}', [SmsAdminController::class, 'saveRechargePlan'])->name('recharge-plans.save');
    Route::get('/users', [SmsAdminController::class, 'users'])->name('users');
    Route::get('/recharges', [SmsAdminController::class, 'recharges'])->name('recharges');
    Route::get('/wallet-logs', [SmsAdminController::class, 'walletLogs'])->name('wallet-logs');
    Route::get('/orders', [SmsAdminController::class, 'orders'])->name('orders');
    Route::get('/logs', [SmsAdminController::class, 'logs'])->name('logs');
});
