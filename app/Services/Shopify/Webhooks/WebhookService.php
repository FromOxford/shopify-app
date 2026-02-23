<?php

namespace App\Services\Shopify\Webhooks;

use App\Models\Shop;
use App\Services\Shopify\Clients\ShopifyClient;

class WebhookService
{
    protected ShopifyClient $client;

    public function __construct(ShopifyClient $client)
    {
        $this->client = $client;
    }

    public function registerAll(Shop $shop): void
    {
        $baseUrl = config('app.url');
        $callbackUrl = "{$baseUrl}/webhooks/shopify";

        $topics = [
            'PRODUCTS_CREATE',
            'PRODUCTS_UPDATE',
            'PRODUCTS_DELETE',
            'CUSTOMERS_CREATE',
            'CUSTOMERS_UPDATE',
            'CUSTOMERS_DELETE',
            'ORDERS_CREATE',
            'ORDERS_UPDATED',
        ];

        foreach ($topics as $topic) {
            $this->register($shop, $topic, $callbackUrl);
        }
    }

    protected function register(Shop $shop, string $topic, string $callbackUrl): void
    {
        $mutation = <<<GQL
            mutation webhookSubscriptionCreate(\$topic: WebhookSubscriptionTopic!, \$callbackUrl: URL!) {
            webhookSubscriptionCreate(
                topic: \$topic,
                webhookSubscription: {
                callbackUrl: \$callbackUrl,
                format: JSON
                }
            ) {
                userErrors {
                field
                message
                }
                webhookSubscription {
                id
                }
            }
            }
            GQL;

        $response = $this->client->query($shop, $mutation, [
            'topic' => $topic,
            'callbackUrl' => $callbackUrl,
        ]);

        $errors = data_get($response, 'data.webhookSubscriptionCreate.userErrors');

        if (!empty($errors)) {
            throw new \RuntimeException(
                'Webhook error: ' . json_encode($errors)
            );
        }
    }
}
