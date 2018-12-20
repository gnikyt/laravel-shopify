<?php

namespace OhMyBrew\ShopifyApp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Package's facade mapper for Laravel.
 */
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
