<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OAuthController;



Route::get('/', fn() => view('welcome'));


Route::get('/install', [OAuthController::class, 'install']);
Route::get('/auth/callback', [OAuthController::class, 'callback']);
