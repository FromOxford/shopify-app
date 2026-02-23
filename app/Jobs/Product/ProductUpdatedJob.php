<?php

namespace App\Jobs\Product;

use App\Models\Shop;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProductUpdatedJob implements ShouldQueue
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
        $firstVariant = $this->data['variants'][0] ?? [];

        Product::updateOrCreate(
            [
                'shop_id' => $this->shopId,
                'shopify_id' => $this->data['id'],
            ],
            [
                'title' => $this->data['title'] ?? '',
                'status' => $this->data['status'] ?? null,
                'price' => isset($firstVariant['price']) ? (float) $firstVariant['price'] : null,
            ]
        );
    }
}
