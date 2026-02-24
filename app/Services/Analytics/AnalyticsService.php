<?php

namespace App\Services\Analytics;

use App\Models\Order;

use App\Models\Shop;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{

    public function dashboardStats(Shop $shop, Carbon $from, Carbon $to): array
    {
        $ordersQuery = Order::query()
            ->where('shop_id', $shop->id)
            ->where('financial_status', 'PAID')
            ->whereBetween('shopify_created_at', [$from, $to]);

        $ordersStats = $ordersQuery
            ->selectRaw('
            SUM(total_price) as revenue,
            COUNT(*) as orders_count,
            AVG(total_price) as average_order_value
        ')
            ->first();

        $customersQuery = Order::query()
            ->where('shop_id', $shop->id)
            ->whereNotNull('customer_id')
            ->whereBetween('shopify_created_at', [$from, $to]);

        $customers = $customersQuery
            ->selectRaw('
            COUNT(DISTINCT customer_id) as total_customers
        ')
            ->first();

        $repeatCustomers = Order::query()
            ->where('shop_id', $shop->id)
            ->where('financial_status', 'PAID')
            ->whereNotNull('customer_id')
            ->whereBetween('shopify_created_at', [$from, $to])
            ->select('customer_id')
            ->groupBy('customer_id')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        $totalCustomers = (int) $customers->total_customers;

        $revenue = (float) $ordersStats->revenue;
        $ordersCount = (int) $ordersStats->orders_count;
        $avg = (float) $ordersStats->average_order_value;

        return [
            'revenue' => $revenue,
            'ordersCount' => $ordersCount,
            'averageOrderValue' => $avg,
            'customers' => $totalCustomers,
            'repeatCustomers' => $repeatCustomers,
            'uniqueCustomers' => max($totalCustomers - $repeatCustomers, 0),
            'repeatCustomerRate' => $totalCustomers
                ? round($repeatCustomers / $totalCustomers * 100, 2)
                : 0,
            'revenuePerCustomer' => $totalCustomers
                ? round($revenue / $totalCustomers, 2)
                : 0,
            'ordersPerCustomer' => $totalCustomers
                ? round($ordersCount / $totalCustomers, 2)
                : 0,
        ];
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
}
