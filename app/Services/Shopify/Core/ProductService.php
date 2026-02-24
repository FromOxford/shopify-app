<?php

namespace App\Services\Shopify\Core;

use App\Models\Product;
use App\Models\Shop;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Traits\ShopifyIdTrait;
use App\Services\Shopify\Clients\ShopifyClient;

class ProductService
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
                $response = $this->client->query($shop, $this->productsQuery(), [
                    'cursor' => $cursor,
                ]);

                $products = Arr::get($response, 'data.products.edges', []);
                $pageInfo = Arr::get($response, 'data.products.pageInfo', []);

                $rows = [];

                foreach ($products as $edge) {
                    $node = $edge['node'] ?? [];
                    $row = $this->mapNodeToRow($shop, $node);

                    if ($row !== null) {
                        $rows[] = $row;
                        $allShopifyIds[] = $row['shopify_id'];
                    }
                }

                if ($rows) {
                    Product::upsert(
                        $rows,
                        ['shop_id', 'shopify_id'],
                        ['title', 'status', 'price']
                    );
                }

                $cursor = $pageInfo['hasNextPage'] ?? false
                    ? ($pageInfo['endCursor'] ?? null)
                    : null;
            } while ($cursor);

            if (!empty($allShopifyIds)) {
                Product::where('shop_id', $shop->id)
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

        $firstVariant = Arr::get($node, 'variants.edges.0.node', []);

        return [
            'shop_id' => $shop->id,
            'shopify_id' => $shopifyId,
            'title' => $node['title'] ?? '',
            'status' => $node['status'] ?? null,
            'price' => isset($firstVariant['price'])
                ? (float) $firstVariant['price']
                : null,
        ];
    }

    protected function productsQuery(): string
    {
        return <<<'GRAPHQL'
            query Products($cursor: String) {
            products(first: 50, after: $cursor) {
                pageInfo {
                hasNextPage
                endCursor
                }
                edges {
                node {
                    id
                    title
                    status
                    variants(first: 1) {
                    edges {
                        node {
                        price
                        }
                    }
                    }
                }
                }
            }
            }
        GRAPHQL;
    }
}
