<?php

use App\Models\Shop;
use App\Services\Shopify\Clients\ShopifyClient;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('services.shopify.version', '2026-01');
});

function fakeShop(array $overrides = []): Shop
{
    return new Shop(array_merge([
        'domain' => 'test.myshopify.com',
        'access_token' => 'token',
    ], $overrides));
}

it('builds correct graphql endpoint', function (): void {
    Http::fake([
        '*' => Http::response(['data' => []], 200),
    ]);

    $client = new ShopifyClient();

    $client->query(fakeShop(), '{ shop { id } }');

    Http::assertSent(fn($request) => $request->url() ===
        'https://test.myshopify.com/admin/api/2026-01/graphql.json');
});

it('sends required shopify headers', function (): void {
    Http::fake([
        '*' => Http::response(['data' => []], 200),
    ]);

    $client = new ShopifyClient();

    $client->query(fakeShop([
        'access_token' => 'secret-token'
    ]), '{ shop { id } }');

    Http::assertSent(fn($request) => $request->hasHeader('X-Shopify-Access-Token', 'secret-token')
        && $request->hasHeader('Content-Type', 'application/json')
        && $request->hasHeader('Accept', 'application/json'));
});

it('sends graphql query with variables', function (): void {
    Http::fake([
        '*' => Http::response(['data' => []], 200),
    ]);

    $client = new ShopifyClient();

    $client->query(
        fakeShop(),
        'query Test($id: ID!) { node(id: $id) { id } }',
        ['id' => '123']
    );

    Http::assertSent(function ($request) {
        $body = $request->data();

        return $body['variables']['id'] === '123';
    });
});

it('throws exception when graphql returns errors', function (): void {
    Http::fake([
        '*' => Http::response([
            'errors' => [
                ['message' => 'Invalid query']
            ]
        ], 200),
    ]);

    $client = new ShopifyClient();

    $client->query(fakeShop(), '{ shop { id } }');
})->throws(RuntimeException::class);

it('throws exception if shop has no access token', function (): void {
    $client = new ShopifyClient();

    $client->query(
        fakeShop(['access_token' => null]),
        '{ shop { id } }'
    );
})->throws(RuntimeException::class, 'Shop does not have an access token.');
