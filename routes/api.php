<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Vận hành trên Render: kiểm tra sức khỏe + việc hằng ngày (xem SystemController)
// Bỏ throttle cho health check: bộ đếm throttle lưu trong cache (MySQL), nếu
// để nguyên thì CSDL trục trặc là health check cũng hỏng -> Render tưởng web
// chết và không cho deploy xong. Health check phải độc lập với CSDL.
Route::get('/healthz', [\App\Http\Controllers\SystemController::class, 'health'])
    ->withoutMiddleware('throttle:api')
    ->name('system.health');
Route::get('/cron/daily', [\App\Http\Controllers\SystemController::class, 'daily'])
    ->middleware('throttle:10,1')
    ->name('system.cron-daily');
