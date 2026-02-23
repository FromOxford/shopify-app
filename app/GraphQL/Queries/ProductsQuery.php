<?php

namespace App\GraphQL\Queries;

use App\Models\Product;
use App\Models\Shop;

class ProductsQuery
{
    public function resolve($_, array $args)
    {
        $shop = Shop::where('domain', '=', $args['shopDomain'])->firstOrFail();
        return Product::where('shop_id', '=', $shop->id)->orderByDesc('id')->get();
    }
}
