<?php

namespace App\Services\Shopify\Core;

use App\Models\Shop;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Traits\ShopifyIdTrait;
use App\Services\Shopify\Clients\ShopifyClient;

class OrderService
{
  use ShopifyIdTrait;
  protected ShopifyClient $client;

  public function __construct(ShopifyClient $client)
  {
    $this->client = $client;
  }

  public function syncFromShopify(Shop $shop): void
  {
    $cursor = null;
    $allShopifyIds = [];

    DB::transaction(function () use ($shop, &$cursor, &$allShopifyIds) {

      $customers = Customer::where('shop_id', $shop->id)
        ->pluck('id', 'shopify_id');

      $products = Product::where('shop_id', $shop->id)
        ->pluck('id', 'shopify_id');

      do {
        $response = $this->client->query($shop, $this->ordersQuery(), [
          'cursor' => $cursor,
        ]);

        $orders = Arr::get($response, 'data.orders.edges', []);
        $pageInfo = Arr::get($response, 'data.orders.pageInfo', []);

        $orderRows = [];
        $orderItemsRows = [];

        foreach ($orders as $edge) {
          $node = $edge['node'] ?? [];

          $shopifyOrderId = $this->decodeShopifyId($node['id'] ?? null);

          if (!$shopifyOrderId) {
            continue;
          }

          $allShopifyIds[] = $shopifyOrderId;

          $customerId = null;

          if (!empty($node['customer']['id'])) {
            $customerShopifyId = $this->decodeShopifyId($node['customer']['id']);
            $customerId = $customers[$customerShopifyId] ?? null;
          }

          $totalPriceSet = Arr::get($node, 'totalPriceSet.shopMoney', []);

          $orderRows[] = [
            'shop_id' => $shop->id,
            'shopify_id' => $shopifyOrderId,
            'customer_id' => $customerId,
            'total_price' => $totalPriceSet['amount'] ?? null,
            'currency' => $totalPriceSet['currencyCode'] ?? null,
            'financial_status' => $node['displayFinancialStatus'] ?? null,
            'fulfillment_status' => $node['displayFulfillmentStatus'] ?? null,
            'shopify_created_at' => isset($node['createdAt'])
              ? Carbon::parse($node['createdAt'])->toDateTimeString()
              : null,
          ];
        }

        if ($orderRows) {
          Order::upsert(
            $orderRows,
            ['shop_id', 'shopify_id'],
            [
              'customer_id',
              'total_price',
              'currency',
              'financial_status',
              'fulfillment_status',
              'shopify_created_at',
            ]
          );
        }

        $ordersMap = Order::where('shop_id', $shop->id)
          ->whereIn('shopify_id', $allShopifyIds)
          ->pluck('id', 'shopify_id');

        foreach ($orders as $edge) {
          $node = $edge['node'] ?? [];
          $shopifyOrderId = $this->decodeShopifyId($node['id'] ?? null);

          if (!$shopifyOrderId) {
            continue;
          }

          $orderId = $ordersMap[$shopifyOrderId] ?? null;

          if (!$orderId) {
            continue;
          }

          $lineItems = Arr::get($node, 'lineItems.edges', []);

          foreach ($lineItems as $itemEdge) {
            $itemNode = $itemEdge['node'] ?? [];

            $productShopifyId = $this->decodeShopifyId($itemNode['product']['id'] ?? null);
            $productId = $products[$productShopifyId] ?? null;
            $shopify_line_item_id = $this->decodeShopifyId($itemNode['id'] ?? null);
            $priceSet = Arr::get($itemNode, 'originalUnitPriceSet.shopMoney', []);

            $orderItemsRows[] = [
              'order_id' => $orderId,
              'product_id' => $productId,
              'shopify_line_item_id' =>  $shopify_line_item_id,
              'title' => $itemNode['title'] ?? '',
              'quantity' => $itemNode['quantity'] ?? 0,
              'price' => $priceSet['amount'] ?? null,
            ];
          }
        }

        if ($orderItemsRows) {
          OrderItem::whereIn('order_id', $ordersMap->values())
            ->delete();

          OrderItem::insert($orderItemsRows);
        }

        $cursor = $pageInfo['hasNextPage'] ?? false
          ? ($pageInfo['endCursor'] ?? null)
          : null;
      } while ($cursor);

      if (!empty($allShopifyIds)) {
        Order::where('shop_id', $shop->id)
          ->whereNotIn('shopify_id', $allShopifyIds)
          ->delete();
      }
    });
  }




  protected function ordersQuery(): string
  {
    return <<<'GRAPHQL'
      query Orders($cursor: String) {
        orders(first: 50, after: $cursor, sortKey: CREATED_AT, reverse: false) {
          pageInfo {
            hasNextPage
            endCursor
          }
          edges {
            node {
              id
              createdAt
              displayFinancialStatus
              displayFulfillmentStatus
              totalPriceSet {
                shopMoney {
                  amount
                  currencyCode
                }
              }
              customer {
                id
                email
                firstName
                lastName
              }
              lineItems(first: 50) {
                edges {
                  node {
                    id
                    title
                    quantity
                    originalUnitPriceSet {
                      shopMoney {
                        amount
                      }
                    }
                    product {
                      id
                      title
                    }
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
