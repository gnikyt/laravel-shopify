<?php

namespace OhMyBrew\ShopifyApp\Test\Stubs;

use OhMyBrew\ShopifyApp\Models\Shop as BaseShop;

class ShopModelStub extends BaseShop
{
    protected $table = 'shops';

    public function hello()
    {
        return 'hello';
    }
}
