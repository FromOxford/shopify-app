<?php

namespace App\Services\Shopify\Core;

use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Traits\ShopifyIdTrait;
use App\Services\Shopify\Clients\ShopifyClient;

class CustomerService
{
    use ShopifyIdTrait;

    public function __construct(protected ShopifyClient $client)
    {
    }

    public function syncFromShopify(Shop $shop): void
    {
        $cursor = null;
        $allShopifyIds = [];

        DB::transaction(function () use ($shop, &$cursor, &$allShopifyIds): void {
            do {
                $response = $this->client->query($shop, $this->customersQuery(), [
                    'cursor' => $cursor,
                ]);

                $customers = Arr::get($response, 'data.customers.edges', []);
                $pageInfo = Arr::get($response, 'data.customers.pageInfo', []);

                $rows = [];

                foreach ($customers as $edge) {
                    $node = $edge['node'] ?? [];
                    $row = $this->mapNodeToRow($shop, $node);

                    if ($row !== null) {
                        $rows[] = $row;
                        $allShopifyIds[] = $row['shopify_id'];
                    }
                }

                if ($rows) {
                    Customer::upsert(
                        $rows,
                        ['shop_id', 'shopify_id'],
                        ['email', 'first_name', 'last_name']
                    );
                }

                $cursor = $pageInfo['hasNextPage'] ?? false
                    ? ($pageInfo['endCursor'] ?? null)
                    : null;
            } while ($cursor);


            if (!empty($allShopifyIds)) {
                Customer::where('shop_id', $shop->id)
                    ->whereNotIn('shopify_id', $allShopifyIds)
                    ->delete();
            }
        });
    }

    protected function mapNodeToRow(Shop $shop, array $node): ?array
    {
        $shopifyId = $this->decodeShopifyId($node['id'] ?? null);

        if (!$shopifyId) {
            return null;
        }

        return [
            'shop_id' => $shop->id,
            'shopify_id' => $shopifyId,
            'email' => $node['email'] ?? null,
            'first_name' => $node['firstName'] ?? null,
            'last_name' => $node['lastName'] ?? null,
        ];
    }



    protected function customersQuery(): string
    {
        return <<<'GRAPHQL'
            query Customers($cursor: String) {
                customers(first: 50, after: $cursor) {
                    pageInfo {
                    hasNextPage
                    endCursor
                    }
                    edges {
                        node {
                            id
                            email
                            firstName
                            lastName
                        }
                    }
                }
            }
            GRAPHQL;
    }
}
