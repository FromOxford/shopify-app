<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Jobs\Shop\RegisterShopJob;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;


final class OAuthController extends Controller
{
    public function install(Request $request)
    {
        // logger('install');
        $request->validate([
            'shop' => ['required', 'regex:/^[a-zA-Z0-9-]+\.myshopify\.com$/']
        ]);
        $shop = $request->input('shop');

        $state = Str::random(32);
        session(['shopify_oauth_state' => $state]);

        $installUrl = "https://{$shop}/admin/oauth/authorize?" . http_build_query([
            'client_id' => config('services.shopify.key'),
            'scope' => 'read_products,write_products,read_orders,read_customers,write_customers',
            'redirect_uri' => config('services.shopify.redirect'),
            'state' => $state,
        ]);

        return redirect($installUrl);
    }

    public function callback(Request $request)
    {
        $request->validate([
            'shop' => ['required', 'regex:/^[a-zA-Z0-9-]+\.myshopify\.com$/'],
            'code' => ['required'],
            'hmac' => ['required'],
            'state' => ['required'],
        ]);

        if ($request->state !== session('shopify_oauth_state')) {
            abort(403, 'Invalid OAuth state');
        }

        $hmac = $request->input('hmac');

        $params = $request->except(['hmac', 'signature']);
        ksort($params);

        $calculated = hash_hmac(
            'sha256',
            urldecode(http_build_query($params)),
            config('services.shopify.secret')
        );

        if (!hash_equals($hmac, $calculated)) {
            abort(403, 'Invalid HMAC');
        }

        $response = Http::post("https://{$request->shop}/admin/oauth/access_token", [
            'client_id' => config('services.shopify.key'),
            'client_secret' => config('services.shopify.secret'),
            'code' => $request->code,
        ]);

        if (!$response->successful()) {
            abort(500, 'Shopify token request failed');
        }

        $shop = Shop::updateOrCreate(
            ['domain' => $request->shop],
            [
                'access_token' => $response->json('access_token'),
                'is_active' => true,
            ]
        );

        RegisterShopJob::dispatch($shop);

        return redirect(config('app.frontend_url') . "/?domain={$request->shop}");
    }
}
