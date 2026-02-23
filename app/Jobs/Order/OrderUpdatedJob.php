<?php

namespace App\Jobs\Order;

use App\Models\Shop;
use App\Models\Order;
use App\Models\Product;
use App\Models\Customer;
use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class OrderUpdatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $shopId;
    protected array $data;

    public function __construct(int $shopId, array $data)
    {
        $this->shopId = $shopId;
        $this->data = $data;
    }

    public function handle()
    {
        DB::transaction(function () {
            $shop = Shop::findOrFail($this->shopId);

            $customerId = null;
            $customerData = $this->data['customer'] ?? null;
            if (!empty($customerData['id'])) {
                $customerId = Customer::where('shop_id', $shop->id)
                    ->where('shopify_id', $customerData['id'])
                    ->value('id');
            }

            $totalPrice = isset($this->data['total_price'])
                ? (float) $this->data['total_price']
                : (float) ($this->data['total_price_set']['shop_money']['amount'] ?? 0);
            $currency = $this->data['currency'] ?? ($this->data['total_price_set']['shop_money']['currency_code'] ?? null);
            $createdAt = $this->data['created_at'] ?? null;

            $order = Order::updateOrCreate(
                [
                    'shop_id' => $shop->id,
                    'shopify_id' => $this->data['id'],
                ],
                [
                    'customer_id' => $customerId,
                    'total_price' => $totalPrice,
                    'currency' => $currency,
                    'financial_status' => $this->data['financial_status'] ?? null,
                    'fulfillment_status' => $this->data['fulfillment_status'] ?? null,
                    'shopify_created_at' => $createdAt ? Carbon::parse($createdAt)->toDateTimeString() : null,
                ]
            );

            $toUpsert = [];
            $lineItems = $this->data['line_items'] ?? [];

            if (!empty($lineItems)) {
                $productIds = Product::where('shop_id', $shop->id)
                    ->whereIn('shopify_id', array_values(array_unique(array_column($lineItems, 'product_id'))))
                    ->pluck('id', 'shopify_id');


                foreach ($lineItems as $item) {
                    $currentQuantity = $item['current_quantity'] ?? $item['quantity'] ?? 0;

                    if ($currentQuantity <= 0) {
                        OrderItem::where('order_id', $order->id)
                            ->where('shopify_line_item_id', $item['id'])
                            ->delete();

                        continue;
                    }

                    $productShopifyId = $item['product_id'] ?? null;
                    $price = isset($item['price'])
                        ? (float) $item['price']
                        : (float) ($item['original_unit_price_set']['shop_money']['amount'] ?? 0);

                    $toUpsert[] = [
                        'order_id' => $order->id,
                        'shopify_line_item_id' => $item['id'],
                        'product_id' => $productShopifyId ? ($productIds[$productShopifyId] ?? null) : null,
                        'title' => $item['title'] ?? '',
                        'quantity' => $currentQuantity,
                        'price' => $price,
                    ];
                }
                if (count($toUpsert) > 0) {
                    OrderItem::upsert(
                        $toUpsert,
                        [
                            'order_id',
                            'shopify_line_item_id'
                        ],
                        [
                            'product_id',
                            'title',
                            'quantity',
                            'price',
                        ]
                    );
                }
            }

            if (!empty($toUpsert)) {
                OrderItem::where('order_id', $order->id)
                    ->whereNotIn(
                        'shopify_line_item_id',
                        collect($toUpsert)->pluck('shopify_line_item_id')
                    )
                    ->delete();
            }
        });
    }
}
