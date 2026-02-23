<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Shop extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain',
        'access_token',
        'is_active',
        'last_products_synced_at',
        'last_orders_synced_at',
        'last_customers_synced_at',
        'sync_status',
        'sync_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_products_synced_at' => 'datetime',
        'last_orders_synced_at' => 'datetime',
        'last_customers_synced_at' => 'datetime',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }


    public function markSyncing(): void
    {
        $this->update([
            'sync_status' => 'syncing',
            'sync_error' => null,
        ]);
    }

    public function markSynced(string $type): void
    {
        $field = match ($type) {
            'products' => 'last_products_synced_at',
            'orders' => 'last_orders_synced_at',
            'customers' => 'last_customers_synced_at',
            default => null,
        };

        if ($field) {
            $this->update([
                $field => Carbon::now(),
                'sync_status' => 'idle',
                'sync_error' => null,
            ]);
        }
    }

    public function markFailed(string $error): void
    {
        $this->update([
            'sync_status' => 'failed',
            'sync_error' => $error,
        ]);
    }
}
