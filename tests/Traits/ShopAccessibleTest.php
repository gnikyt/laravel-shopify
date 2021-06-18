<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Test\Traits\Stubs\TestShopAccessible;

class ShopAccessibleTest extends TestCase
{
    public function testSuccess(): void
    {
        $class = new TestShopAccessible();
        $class->setShop(
            factory($this->model)->create()
        );

        $this->assertTrue($class->hasShop());
    }
}
