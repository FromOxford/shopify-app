<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'shop_id',
        'shopify_id',
        'customer_id',
        'total_price',
        'currency',
        'financial_status',
        'fulfillment_status',
        'shopify_created_at'
    ];

    protected $dates = [
        'shopify_created_at'
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
