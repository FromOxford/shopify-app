<?php

namespace App\GraphQL\Queries;

use App\Models\Customer;
use App\Models\Shop;

class CustomersQuery
{
    public function resolve($_, array $args)
    {
        $shop = Shop::where('domain', '=', $args['shopDomain'])->firstOrFail();
        return Customer::where('shop_id', '=', $shop->id)->orderByDesc('id')->get();
    }
}
