<?php

namespace OhMyBrew\ShopifyApp\Test\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Session;
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
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        Config::set('shopify-app.api_class', new ApiStub());
    }

    public function testStoresSession()
    {
        // Create the shop
        $shop = factory(Shop::class)->create();

        // Store the session
        $as = new AuthShopHandler($shop->shopify_domain);
        $as->storeSession();

        $this->assertEquals($shop->shopify_domain, Session::get('shopify_domain'));
    }

    public function testAuthUrl()
    {
        // Create the shop
        $shop = factory(Shop::class)->create();

        // Get the URL
        $as = new AuthShopHandler($shop->shopify_domain);
        $url = $as->buildAuthUrl();

        $this->assertEquals(
            "https://{$shop->shopify_domain}/admin/oauth/authorize?client_id=&scope=read_products,write_products&redirect_uri=https://localhost/authenticate",
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

    public function testStoresAccessToken()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_access_token',
        ]);

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the call
        $currentToken = $shop->shopify_token;
        $as = new AuthShopHandler($shop->shopify_domain);
        $as->storeAccessToken('1234');

        // Refresh
        $shop->refresh();

        $this->assertTrue($currentToken !== $shop->shopify_token);
    }

    public function testStoresAccessTokenForTrashedShop()
    {
        // Stub the responses
        ApiStub::stubResponses([
            'get_access_token',
        ]);

        // Create the shop
        $shop = factory(Shop::class)->create();
        $shop->delete();

        // Run the call
        $currentToken = $shop->shopify_token;
        $as = new AuthShopHandler($shop->shopify_domain);
        $as->storeAccessToken('1234');

        // Refresh
        $shop->refresh();

        $this->assertTrue($currentToken !== $shop->shopify_token);
    }

    public function testJobsDoNotRun()
    {
        // Fake the queue
        Queue::fake();

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the jobs
        $as = new AuthShopHandler($shop->shopify_domain);
        $as->dispatchJobs();

        // No jobs should be pushed when theres no config for them
        Queue::assertNotPushed(WebhookInstaller::class);
        Queue::assertNotPushed(ScripttagInstaller::class);
    }

    /**
     * @expectedException Exception
     */
    public function testJobsDoNotRunForMissingToken()
    {
        // Create the shop
        $shop = factory(Shop::class)->create([
            'shopify_token' => null,
        ]);

        // Run the jobs
        $as = new AuthShopHandler($shop->shopify_domain);
        $as->dispatchJobs();
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

        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        Config::set('shopify-app.after_authenticate_job', [[
            'job'    => $jobClass,
            'inline' => false,
        ]]);

        // Create the shop
        $shop = factory(Shop::class)->create();

        // Run the jobs
        $as = new AuthShopHandler($shop->shopify_domain);
        $as->dispatchJobs();

        // No jobs should be pushed when theres no config for them
        Queue::assertPushed(WebhookInstaller::class);
        Queue::assertPushed(ScripttagInstaller::class);
        Queue::assertPushed($jobClass);
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
        $as = new AuthShopHandler($shop->shopify_domain);
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
        $as = new AuthShopHandler($shop->shopify_domain);

        $this->assertTrue($as->dispatchJobs());
    }
}
