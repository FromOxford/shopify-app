<?php

use App\Services\ShopifyService;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;
use App\Http\Controllers\ShopifyWebhookController;


Route::get('/', function () {
    return view('welcome');
});
// Route::get('/dashboard', function () {
//     return 'Dashboard - токен получен, OAuth прошёл успешно!';
// });


// Route::get('/products', function () {
//     $shopDomain = 'test-laravel-lighthouse.myshopify.com';
//     $service = new ShopifyService($shopDomain);
//     $service->syncAll();
//     return 'ok';
// });

Route::get('/install', [OAuthController::class, 'install']);
Route::get('/auth/callback', [OAuthController::class, 'callback']);

// Все SPA маршруты отдаём Nuxt
Route::get('/{any}', function () {
    return view('app'); // app.blade.php подключает Nuxt bundle
})->where('any', '.*');
