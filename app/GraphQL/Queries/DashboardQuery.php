<?php

namespace App\GraphQL\Queries;

use App\Models\Shop;
use App\Services\Analytics\AnalyticsService;
use Carbon\Carbon;

class DashboardQuery
{
    public function __construct(
        protected AnalyticsService $analytics
    ) {}

    public function resolve($_, array $args)
    {
        $shop = Shop::where('domain', $args['shopDomain'])->firstOrFail();

        $range = $args['range'] ?? null;

        if (is_array($range)) {
            $from = isset($range['from'])
                ? Carbon::parse($range['from'])
                : now()->subDays(30);

            $to = isset($range['to'])
                ? Carbon::parse($range['to'])
                : now();
        } else {
            $from = now()->subDays(30);
            $to = now();
        }

        return [
            'revenue' => $this->analytics->revenue($shop, $from, $to),
            'ordersCount' => $this->analytics->ordersCount($shop, $from, $to),
            'averageOrderValue' => $this->analytics->averageOrderValue($shop, $from, $to),

            'customers' => $this->analytics->customersCount($shop, $from, $to),
            'repeatCustomers' => $this->analytics->repeatCustomersCount($shop, $from, $to),
            'uniqueCustomers' => $this->analytics->uniqueCustomersCount($shop, $from, $to),

            'repeatCustomerRate' => $this->analytics->repeatCustomerRate($shop, $from, $to),
            'revenuePerCustomer' => $this->analytics->revenuePerCustomer($shop, $from, $to),
            'ordersPerCustomer' => $this->analytics->ordersPerCustomer($shop, $from, $to),
            'revenueByDay' => $this->analytics->revenueByDay($shop, $from, $to),
        ];;
    }
}
