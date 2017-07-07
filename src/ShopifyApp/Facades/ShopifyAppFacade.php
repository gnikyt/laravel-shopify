<?php namespace OhMyBrew\ShopifyApp\Facades;

use Illuminate\Support\Facades\Facade;

class ShopifyAppFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'ShopifyApp';
    }
}
