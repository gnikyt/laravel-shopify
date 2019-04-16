<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;

require_once __DIR__.'/../Stubs/AfterAuthenticateJobStub.php';

class AuthShopHandlerTest extends TestCase
{
    public function setUp() : void
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testAuthUrl()
    {
        // Create the shop
        $shop = factory(Shop::class)->create();

        // Get the URL
        $as = new AuthShopHandler($shop);

        $url = $as->buildAuthUrl(null);
        $this->assertEquals(
            "https://{$shop->shopify_domain}/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate",
            $url
        );

        $url = $as->buildAuthUrl('per-user');
        $this->assertEquals(
            "https://{$shop->shopify_domain}/admin/oauth/authorize?client_id=&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalhost%2Fauthenticate&grant_options%5B%5D=per-user",
            $url
        );
    }

    public function testVerifyRequest()
    {
        // Create the shop
        $shop = factory(Shop::class)->create();

        // Build the data
        $data = [
            'shop'      => $shop->shopify_domain,
            'timestamp' => time(),
            'protocol'  => 'https',
        ];
        $hmac = ShopifyApp::createHmac([
            'data'               => $data,
            'buildQuery'         => true,
            'buildQueryWithJoin' => true,
        ]);
        $data['hmac'] = $hmac;

        // Run the verify
        $result = (new AuthShopHandler($shop))->verifyRequest($data);

        $this->assertTrue($result);
    }

    public function testPostProcessForTrashedShop()
    {
        // Create the shop
        $shop = factory(Shop::class)->create();
        $shop->delete();

        // Confirm trashed
        $this->assertTrue($shop->trashed());

        // Run the call
        $as = new AuthShopHandler($shop);
        $as->postProcess();

        $shop->refresh();

        // Confirm its not longer trashed
        $this->assertFalse($shop->trashed());
    }

    public function testJobsDoNotRun()
    {
        // Fake the queue
        Queue::fake();

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the jobs
        $as = new AuthShopHandler($shop);
        $as->dispatchJobs();

        // No jobs should be pushed when theres no config for them
        Queue::assertNotPushed(WebhookInstaller::class);
        Queue::assertNotPushed(ScripttagInstaller::class);
    }

    public function testJobsRun()
    {
        // Fake the queue
        Queue::fake();

        // Config setup
        Config::set('shopify-app.webhooks', [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ]);
        Config::set('shopify-app.scripttags', [
            [
                'src' => 'https://localhost/scripts/file.js',
            ],
        ]);
        Config::set('shopify-app.job_queues.webhooks', 'custom-queue');

        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        Config::set('shopify-app.after_authenticate_job', [[
            'job'    => $jobClass,
            'inline' => false,
        ]]);

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the jobs
        $as = new AuthShopHandler($shop);
        $as->dispatchJobs();

        // No jobs should be pushed when theres no config for them
        Queue::assertPushed(WebhookInstaller::class);
        Queue::assertPushed(ScripttagInstaller::class);
        Queue::assertPushed($jobClass);
        Queue::assertPushedOn('custom-queue', WebhookInstaller::class);
    }

    public function testAfterAuthenticateSingleJobRuns()
    {
        // Fake the queue
        Queue::fake();

        // Create the config
        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        Config::set('shopify-app.after_authenticate_job', [
            'job'    => $jobClass,
            'inline' => false,
        ]);

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the jobs
        $as = new AuthShopHandler($shop);
        $as->dispatchJobs();

        Queue::assertPushed($jobClass);
    }

    public function testAfterAuthenticateInlineJobRuns()
    {
        // Create the config
        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        Config::set('shopify-app.after_authenticate_job', [
            'job'    => $jobClass,
            'inline' => true,
        ]);

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the jobs
        $as = new AuthShopHandler($shop);

        $this->assertTrue($as->dispatchJobs());
    }
}
