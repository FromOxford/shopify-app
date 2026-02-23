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

class OrderCreatedJob implements ShouldQueue
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

            $customerId = null;
            $customerData = $this->data['customer'] ?? null;

            if (!empty($customerData['id'])) {
                $customerId = Customer::where('shop_id', $this->shopId)
                    ->where('shopify_id', (string) $customerData['id'])
                    ->value('id');
            }

            $totalPrice = isset($this->data['total_price'])
                ? (float) $this->data['total_price']
                : (float) ($this->data['total_price_set']['shop_money']['amount'] ?? 0);

            $currency = $this->data['currency']
                ?? ($this->data['total_price_set']['shop_money']['currency_code'] ?? null);

            $createdAt = !empty($this->data['created_at'])
                ? Carbon::parse($this->data['created_at'])->toDateTimeString()
                : null;

            $order = Order::updateOrCreate(
                [
                    'shop_id' => $this->shopId,
                    'shopify_id' => (string) $this->data['id'],
                ],
                [
                    'customer_id' => $customerId,
                    'total_price' => $totalPrice,
                    'currency' => $currency,
                    'financial_status' => $this->data['financial_status'] ?? null,
                    'fulfillment_status' => $this->data['fulfillment_status'] ?? null,
                    'shopify_created_at' => $createdAt,
                ]
            );

            $lineItems = $this->data['line_items'] ?? [];
            $items = [];

            if (!empty($lineItems)) {

                $productShopifyIds = collect($lineItems)
                    ->pluck('product_id')
                    ->filter()
                    ->unique()
                    ->values();

                $productIds = Product::where('shop_id', $this->shopId)
                    ->whereIn('shopify_id', $productShopifyIds)
                    ->pluck('id', 'shopify_id');

                foreach ($lineItems as $item) {

                    if (empty($item['id'])) {
                        continue;
                    }

                    $price = isset($item['price'])
                        ? (float) $item['price']
                        : (float) ($item['original_unit_price_set']['shop_money']['amount'] ?? 0);

                    $items[] = [
                        'order_id' => $order->id,
                        'product_id' => $productIds[$item['product_id']] ?? null,
                        'shopify_line_item_id' => (string) $item['id'],
                        'title' => $item['title'] ?? '',
                        'quantity' => (int) ($item['quantity'] ?? 0),
                        'price' => $price,
                    ];
                }
            }

            if (!empty($items)) {
                OrderItem::upsert(
                    $items,
                    ['order_id', 'shopify_line_item_id'],
                    ['product_id', 'title', 'quantity', 'price']
                );
            }
        });
    }
}
