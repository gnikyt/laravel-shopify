<?php

namespace OhMyBrew\ShopifyApp\Test\Actions;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Actions\DeleteWebhooks;
use OhMyBrew\ShopifyApp\Test\Stubs\Api as ApiStub;

class DeleteWebhooksTestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(DeleteWebhooks::class);
    }

    public function testShouldDelete(): void
    {
        // Setup API stub
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_webhooks',
            'empty',
            'empty'
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func($this->action, $shop->getId());

        $this->assertEquals(2, count($result)); // 2 from fixture file
    }
}
