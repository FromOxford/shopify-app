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

class CustomerUpdatedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected int $shopId, protected array $data)
    {
    }

    public function handle()
    {
        DB::transaction(function (): void {


            $email = $this->data['email'] ?? null;
            $firstName = $this->data['first_name'] ?? ($this->data['firstName'] ?? null);
            $lastName = $this->data['last_name'] ?? ($this->data['lastName'] ?? null);

            Customer::updateOrCreate(
                [
                    'shop_id' => $this->shopId,
                    'shopify_id' => $this->data['id'],
                ],
                [
                    'email' => $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                ]
            );
        });
    }
}
