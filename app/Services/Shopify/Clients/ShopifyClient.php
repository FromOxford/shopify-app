<?php

namespace App\Services\Shopify\Clients;

use App\Models\Shop;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ShopifyClient
{
    protected string $apiVersion;

    public function __construct(?string $apiVersion = null)
    {
        $this->apiVersion = $apiVersion ?: config('services.shopify.version', '2026-01');
    }

    public function query(Shop $shop, string $query, ?array $variables = null): array
    {
        $endpoint = $this->buildEndpoint($shop);


        $response = $this->http($shop)->post($endpoint, [
            'query' => $query,
            'variables' => $variables,
        ])->throw();

        $data = $response->json();

        if (!empty($data['errors'])) {
            $messages = collect($data['errors'])
                ->pluck('message')
                ->implode(' | ');

            throw new \RuntimeException(
                'Shopify GraphQL error: ' . $messages
            );
        }

        return $data;
    }

    protected function http(Shop $shop): PendingRequest
    {
        if (!$shop->access_token) {
            throw new \RuntimeException('Shop does not have an access token.');
        }

        return Http::withHeaders([
            'X-Shopify-Access-Token' => $shop->access_token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    protected function buildEndpoint(Shop $shop): string
    {
        return "https://{$shop->domain}/admin/api/{$this->apiVersion}/graphql.json";
    }
}
