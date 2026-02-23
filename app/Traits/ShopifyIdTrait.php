<?php

namespace App\Traits;

trait ShopifyIdTrait
{
    protected function decodeShopifyId(?string $gid): ?int
    {
        logger($gid);
        if (!$gid) {
            return null;
        }

        if (strpos($gid, '/') === false) {
            return (int) $gid;
        }

        $parts = explode('/', $gid);

        return (int) end($parts);
    }
}
