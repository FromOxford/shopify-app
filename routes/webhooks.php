<?php


use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ShopifyWebhookController;

Route::post('/shopify', [ShopifyWebhookController::class, 'handle']);
