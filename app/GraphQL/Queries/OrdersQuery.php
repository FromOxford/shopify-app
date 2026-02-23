<?php

namespace App\GraphQL\Queries;

use App\Models\Order;
use App\Models\Shop;
use Carbon\Carbon;

class OrdersQuery
{
    public function resolve($_, array $args)
    {
        $shop = Shop::where('domain', $args['shopDomain'])->firstOrFail();

        $from = isset($args['from']) ? Carbon::parse($args['from']) : null;
        $to = isset($args['to']) ? Carbon::parse($args['to']) : null;

        return Order::query()
            ->with(['items', 'customer'])
            ->where('shop_id', $shop->id)

            ->when(
                $args['financial_status'] ?? null,
                fn($q, $status) =>
                $q->where('financial_status', strtoupper($status))
            )

            ->when(
                $args['fulfillment_status'] ?? null,
                fn($q, $status) =>
                $q->where('fulfillment_status', strtoupper($status))
            )

            ->when(
                $from,
                fn($q) =>
                $q->where('shopify_created_at', '>=', $from)
            )

            ->when(
                $to,
                fn($q) =>
                $q->where('shopify_created_at', '<=', $to)
            )

            ->orderByDesc('id')
            ->get();
    }
}
