<?php

use App\Models\Shop;
use App\Jobs\Shop\RegisterShopJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    config()->set('services.shopify.key', 'test_key');
    config()->set('services.shopify.secret', 'test_secret');
    config()->set('services.shopify.redirect', 'http://localhost/auth/callback');
});

it('redirects to shopify install url and stores state', function () {

    $response = $this->get('/install?shop=test-shop.myshopify.com');

    $response->assertRedirect();

    expect(session()->has('shopify_oauth_state'))->toBeTrue();

    expect($response->headers->get('Location'))
        ->toContain('https://test-shop.myshopify.com/admin/oauth/authorize');
});

it('handles successful oauth callback', function () {

    Queue::fake();

    Http::fake([
        '*' => Http::response(['access_token' => 'new_token'], 200),
    ]);

    $state = 'valid_state';
    session(['shopify_oauth_state' => $state]);

    $params = [
        'shop' => 'test-shop.myshopify.com',
        'code' => 'test_code',
        'state' => $state,
    ];

    ksort($params);

    $hmac = hash_hmac(
        'sha256',
        urldecode(http_build_query($params)),
        config('services.shopify.secret')
    );

    $response = $this->get('/auth/callback?' . http_build_query(array_merge(
        $params,
        ['hmac' => $hmac]
    )));

    $response->assertRedirect();

    $this->assertDatabaseHas('shops', [
        'domain' => 'test-shop.myshopify.com',
        'access_token' => 'new_token',
    ]);

    Queue::assertPushed(RegisterShopJob::class);
});

it('fails when oauth state is invalid', function () {

    session(['shopify_oauth_state' => 'correct']);

    $response = $this->get('/auth/callback?shop=test.myshopify.com&code=1&state=wrong&hmac=abc');

    $response->assertStatus(403);
});

it('fails when hmac is invalid', function () {

    $state = 'valid_state';
    session(['shopify_oauth_state' => $state]);

    $response = $this->get('/auth/callback?' . http_build_query([
        'shop' => 'test-shop.myshopify.com',
        'code' => 'test_code',
        'state' => $state,
        'hmac' => 'invalid_hmac',
    ]));

    $response->assertStatus(403);
});
