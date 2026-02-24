<?php

namespace App\Jobs\Shop;

use App\Models\Shop;
use App\Services\Shopify\Core\ShopifyService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegisterShopJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $tries = 3;
    public $backoff = [10, 30, 60];
    public function __construct(protected Shop $shop)
    {
    }

    public function handle(ShopifyService $service)
    {
        logger("RegisterShopJob");
        $service
            ->forShop($this->shop)
            ->install();
    }
}
