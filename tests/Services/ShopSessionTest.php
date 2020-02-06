<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Test\TestCase;

class ShopSessionTest extends TestCase
{
    protected $shopSession;
    protected $model;

    public function setUp(): void
    {
        parent::setUp();

        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testMakeLogsInShop(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Login the shop
        $this->shopSession->make($shop->getDomain());

        $this->assertFalse($this->shopSession->guest());
        $this->assertInstanceOf(IShopModel::class, $this->shopSession->get());
    }
}
