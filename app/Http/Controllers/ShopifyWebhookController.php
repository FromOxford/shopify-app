<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use App\Jobs\Product\ProductCreatedJob;
use App\Jobs\Product\ProductDeletedJob;
use App\Jobs\Product\ProductUpdatedJob;
use App\Jobs\Customer\CustomerCreatedJob;
use App\Jobs\Customer\CustomerDeletedJob;
use App\Jobs\Customer\CustomerUpdatedJob;
use App\Jobs\Order\OrderCreatedJob;
use App\Jobs\Order\OrderUpdatedJob;


class ShopifyWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $this->verifyWebhook($request);

        $topic = $request->header('X-Shopify-Topic');
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $data = $request->all();

        $shop = Shop::where('domain', $shopDomain)->firstOrFail();

        // Log::info('Webhook verified', [
        //     'topic' => $topic,
        //     'shop' => $shopDomain,
        //     'data' => $data,
        // ]);

        switch ($topic) {
            case 'products/create':
                ProductCreatedJob::dispatch($shop->id, $data);
                break;
            case 'products/update':
                ProductUpdatedJob::dispatch($shop->id, $data);
                break;
            case 'products/delete':
                ProductDeletedJob::dispatch($shop->id, $data['id']);
                break;
            case 'customers/create':
                CustomerCreatedJob::dispatch($shop->id, $data);
                break;
            case 'customers/update':
                CustomerUpdatedJob::dispatch($shop->id, $data);
                break;
            case 'customers/delete':
                CustomerDeletedJob::dispatch($shop->id, $data['id']);
                break;
            case 'orders/create':
                OrderCreatedJob::dispatch($shop->id, $data);
                break;
            case 'orders/updated':
                OrderUpdatedJob::dispatch($shop->id, $data);
                break;
        }

        return response()->json(['status' => 'ok']);
    }

    protected function verifyWebhook(Request $request): void
    {
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
        $data = $request->getContent();

        $calculatedHmac = base64_encode(
            hash_hmac(
                'sha256',
                $data,
                config('services.shopify.secret'),
                true
            )
        );

        if (!hash_equals($hmacHeader, $calculatedHmac)) {
            abort(401, 'Invalid webhook signature');
        }
    }
}
