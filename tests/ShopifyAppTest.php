<?php

namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\ShopifyApp;

class ShopifyAppTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->shopifyApp = new ShopifyApp($this->app);
    }
}
