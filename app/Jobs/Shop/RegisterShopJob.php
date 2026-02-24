<?php

namespace App\Jobs\Shop;

use App\Models\Shop;
use App\Services\Shopify\Core\ShopifyService;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;

class RegisterShopJob implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(protected Shop $shop) {}

    public function uniqueId(): string
    {
        return 'register-shop-' . $this->shop->id;
    }

    public function handle(ShopifyService $service)
    {
        $this->shop->markSyncing();

        try {
            $service
                ->forShop($this->shop)
                ->install();

            $this->shop->update([
                'sync_status' => 'idle',
            ]);
        } catch (\Throwable $e) {
            $this->shop->markFailed($e->getMessage());

            throw $e;
        }
    }
}
