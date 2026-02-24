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
        return array_merge(
            $this->analytics->dashboardStats($shop, $from, $to),
            [
                'revenueByDay' => $this->analytics->revenueByDay($shop, $from, $to),
            ]
        );
    }
}
