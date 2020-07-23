<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Traits\ShopAccessible;

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

/**
 * Stub.
 */
class TestShopAccessible
{
    use ShopAccessible;

    public function hasShop(): bool
    {
        return $this->shop !== null;
    }
}
