<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Illuminate\Support\Facades\Queue;
use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Actions\AfterAuthorize;

require_once __DIR__.'/../Stubs/AfterAuthorizeJob.php';

class AfterAuthorizeTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\AfterAuthorize
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();

        $this->action = $this->app->make(AfterAuthorize::class);
    }

    public function testRunDispatch(): void
    {
        // Fake the queue
        Queue::fake();

        // Create the config
        $jobClass = \App\Jobs\AfterAuthorizeJob::class;
        $this->app['config']->set('shopify-app.after_authenticate_job', [
            [
                'job'    => $jobClass,
                'inline' => false,
            ],
            [
                'job'    => $jobClass,
                'inline' => false,
            ],
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        call_user_func(
            $this->action,
            $shop->getId(0)
        );

        Queue::assertPushed($jobClass);
    }

    public function testRunInline(): void
    {
        // Create the config
        $jobClass = \App\Jobs\AfterAuthorizeJob::class;
        $this->app['config']->set('shopify-app.after_authenticate_job', [
            'job'    => $jobClass,
            'inline' => true,
        ]);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(0)
        );

        $this->assertTrue($result);
    }

    public function testRunNoJobs(): void
    {
        // Create the config
        $this->app['config']->set('shopify-app.after_authenticate_job', []);

        // Create the shop
        $shop = factory($this->model)->create();

        // Run
        $result = call_user_func(
            $this->action,
            $shop->getId(0)
        );

        $this->assertFalse($result);
    }
}
