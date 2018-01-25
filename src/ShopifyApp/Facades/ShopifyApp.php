<?php

namespace OhMyBrew\ShopifyApp\Facades;

use Illuminate\Support\Facades\Facade;

class ShopifyApp extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'shopifyapp';
    }
}
