<?php

namespace App\Services\Shopify\Core;

use App\Models\Shop;
use App\Services\Shopify\Clients\ShopifyClient;
use Illuminate\Support\Facades\Log;
use Throwable;

class ShopifyService
{
    protected Shop $shop;

    public function __construct(
        protected ShopifyClient $client,
        protected ProductService $products,
        protected CustomerService $customers,
        protected OrderService $orders,
    ) {}

    public function forShop(Shop $shop): self
    {
        $this->shop = $shop;

        return $this;
    }

    /*
     INSTALL FLOW
    */

    public function install(): void
    {
        logger('registerWebhooks & syncAll');
        $this->registerWebhooks();
        $this->syncAll();
    }

    /*
     WEBHOOK REGISTRATION
    */

    protected function registerWebhooks(): void
    {
        $callbackUrl = config('app.url') . '/webhooks/shopify';

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

        // $existingTopics = $this->getExistingWebhookTopics(); // giving an error TODO return logic and debug

        foreach ($topics as $topic) {
            // if (in_array($topic, $existingTopics)) {
            //     continue; // уже зарегистрирован
            // }

            $this->registerWebhook($topic, $callbackUrl);
        }
    }

    protected function getExistingWebhookTopics(): array
    {
        $query = <<<GQL
        {
            webhookSubscriptions(first: 50) {
                edges {
                    node {
                        topic
                    }
                }
            }
        }
        GQL;

        $response = $this->client->query($this->shop, $query);

        return collect(
            data_get($response, 'data.webhookSubscriptions.edges', [])
        )
            ->pluck('node.topic')
            ->toArray();
    }

    protected function registerWebhook(string $topic, string $callbackUrl): void
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

        $response = $this->client->query($this->shop, $mutation, [
            'topic' => $topic,
            'callbackUrl' => $callbackUrl,
        ]);

        $errors = data_get($response, 'data.webhookSubscriptionCreate.userErrors');

        if (!empty($errors)) {
            Log::error('Shopify webhook error', [
                'shop' => $this->shop->domain,
                'topic' => $topic,
                'errors' => $errors,
            ]);
        }
    }

    /*
     SYNC
    */

    public function syncAll(): void
    {
        $this->shop->markSyncing();

        $this->syncSafely('products', fn() => $this->products->syncFromShopify($this->shop));
        $this->syncSafely('customers', fn() => $this->customers->syncFromShopify($this->shop));
        $this->syncSafely('orders', fn() => $this->orders->syncFromShopify($this->shop));

        $this->shop->update([
            'sync_status' => 'idle',
        ]);
    }

    protected function syncSafely(string $resource, callable $callback): void
    {
        try {
            $callback();
            $this->shop->markSynced($resource);
        } catch (Throwable $e) {
            $this->shop->markFailed("$resource sync failed: " . $e->getMessage());

            Log::error("Shopify $resource sync failed", [
                'shop' => $this->shop->domain,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
