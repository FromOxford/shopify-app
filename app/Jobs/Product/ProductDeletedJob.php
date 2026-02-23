<?php

namespace App\Jobs\Product;

use App\Models\Shop;
use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProductDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $shopId;
    protected int $shopifyId;

    public function __construct(int $shopId, int $id)
    {
        $this->shopId = $shopId;
        $this->shopifyId = $id;
    }

    public function handle()
    {
        Product::where('shop_id', $this->shopId)
            ->where('shopify_id', $this->shopifyId)
            ->delete();
    }
}
