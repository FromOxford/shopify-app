<?php

namespace App\Jobs\Customer;

use App\Models\Shop;
use App\Models\Customer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class CustomerDeletedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $shopId, protected int $shopifyId)
    {
    }

    public function handle()
    {
        DB::transaction(function (): void {
            Customer::where('shop_id',  $this->shopId)
                ->where('shopify_id', $this->shopifyId)
                ->delete();
        });
    }
}
