<?php

namespace App\Services\Analytics;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    protected function paidOrdersQuery(Shop $shop, ?Carbon $from = null, ?Carbon $to = null)
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->where('financial_status', 'PAID')
            ->when($from, fn($query) => $query->where('shopify_created_at', '>=', $from))
            ->when($to, fn($query) => $query->where('shopify_created_at', '<=', $to));
    }

    public function revenue(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->paidOrdersQuery($shop, $from, $to)
            ->sum('total_price');
    }

    public function ordersCount(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): int
    {
        return $this->paidOrdersQuery($shop, $from, $to)->count();
    }

    public function averageOrderValue(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return (float) $this->paidOrdersQuery($shop, $from, $to)
            ->avg('total_price') ?? 0;
    }

    public function customersCount(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): int
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->whereNotNull('customer_id')
            ->when($from, fn($query) => $query->where('shopify_created_at', '>=', $from))
            ->when($to, fn($query) => $query->where('shopify_created_at', '<=', $to))
            ->select('customer_id')
            ->groupBy('customer_id')
            // ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }

    public function uniqueCustomersCount(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): int
    {
        $total = $this->customersCount($shop, $from, $to);
        $repeat = $this->repeatCustomersCount($shop, $from, $to);

        return max($total - $repeat, 0);
    }
    public function repeatCustomersCount(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): int
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->whereNotNull('customer_id')
            ->where('financial_status', 'PAID')
            ->when($from, fn($query) => $query->where('shopify_created_at', '>=', $from))
            ->when($to, fn($query) => $query->where('shopify_created_at', '<=', $to))
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();
    }


    public function revenueByDay(Shop $shop, Carbon $from, Carbon $to): Collection
    {
        return Order::query()
            ->where('shop_id', $shop->id)
            ->where('financial_status', 'PAID')
            ->whereBetween('shopify_created_at', [$from, $to])
            ->select(
                DB::raw('DATE(shopify_created_at) as date'),
                DB::raw('SUM(total_price) as revenue'),
                DB::raw('COUNT(*) as orders_count')
            )
            ->groupBy(DB::raw('DATE(shopify_created_at)'))
            ->orderBy('date')
            ->get();
    }

    public function repeatCustomerRate(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $total = $this->customersCount($shop, $from, $to);

        if ($total === 0) {
            return 0;
        }

        return round(
            $this->repeatCustomersCount($shop, $from, $to) / $total * 100,
            2
        );
    }

    public function revenuePerCustomer(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $customers = $this->customersCount($shop, $from, $to);

        if ($customers === 0) {
            return 0;
        }

        return round(
            $this->revenue($shop, $from, $to) / $customers,
            2
        );
    }

    public function ordersPerCustomer(Shop $shop, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $customers = $this->customersCount($shop, $from, $to);

        if ($customers === 0) {
            return 0;
        }

        return round(
            $this->ordersCount($shop, $from, $to) / $customers,
            2
        );
    }
}
