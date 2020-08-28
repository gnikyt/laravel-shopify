<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Actions\DeleteWebhooks;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;

class DeleteWebhooksTestTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\DeleteWebhooks
     */
    protected $action;

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
