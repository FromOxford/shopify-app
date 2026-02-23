<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'shop_id',
        'shopify_id',
        'title',
        'price',
        'status'
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
