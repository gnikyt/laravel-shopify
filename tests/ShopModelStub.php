<?php namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Models\Shop as BaseShop;

class ShopModelStub extends BaseShop
{
    protected $table = 'shops';

    public function hello()
    {
        return 'hello';
    }
}
