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

    public function __construct(protected int $shopId, protected int $shopifyId)
    {
    }

    public function handle()
    {
        Product::where('shop_id', $this->shopId)
            ->where('shopify_id', $this->shopifyId)
            ->delete();
    }
}
