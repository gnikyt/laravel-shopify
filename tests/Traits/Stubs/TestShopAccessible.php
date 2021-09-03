<?php

namespace Osiset\ShopifyApp\Test\Traits\Stubs;

use Osiset\ShopifyApp\Traits\ShopAccessible;

class TestShopAccessible
{
    use ShopAccessible;

    public function hasShop(): bool
    {
        return $this->shop !== null;
    }
}
