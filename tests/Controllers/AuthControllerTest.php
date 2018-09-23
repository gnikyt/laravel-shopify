<?php

namespace OhMyBrew\ShopifyApp\Test\Controllers;

use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Controllers\AuthController;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;
use OhMyBrew\ShopifyApp\Test\TestCase;
use ReflectionMethod;

require_once __DIR__.'/../Stubs/AfterAuthenticateJobStub.php';

class AuthControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub()]);

        // HMAC for regular tests
        $this->hmac = 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163';
        $this->hmacParams = [
            'hmac'      => $this->hmac,
            'shop'      => 'example.myshopify.com',
            'code'      => '1234678',
            'timestamp' => '1337178173',
        ];

        // HMAC for trashed shop testing
        $this->hmacTrashed = '77ec82b8dca7ea606e8b69d3cc50beced069b03631fd6a2f367993eb793f4c45';
        $this->hmacTrashedParams = [
            'hmac'      => $this->hmacTrashed,
            'shop'      => 'trashed-shop.myshopify.com',
            'code'      => '1234678910',
            'timestamp' => '1337178173',
        ];
    }

    public function testLoginTest()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function testAuthRedirectsBackToLoginWhenNoShop()
    {
        $response = $this->post('/authenticate');

        $response->assertStatus(302);
        $response->assertRedirect('http://localhost/login');
    }

    public function testAuthRedirectsUserToAuthScreenWhenNoCode()
    {
        // Default for Laravel
        $this->assertNull(config('session.expire_on_close'));

        // Run the request
        $response = $this->post('/authenticate', ['shop' => 'example.myshopify.com']);

        // Check the view
        $response->assertSessionHas('shopify_domain');
        $response->assertViewHas('shopDomain', 'example.myshopify.com');
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id=&scope=read_products,write_products&redirect_uri=https://localhost/authenticate'
        );

        // Override in auth for a single request
        $this->assertTrue(config('session.expire_on_close'));
    }

    public function testAuthAcceptsShopWithCodeAndUpdatesTokenForShop()
    {
        $response = $this->call('get', '/authenticate', $this->hmacParams);

        // Previous token was 1234
        $this->assertEquals(
            '12345678',
            Shop::where('shopify_domain', 'example.myshopify.com')->first()->shopify_token
        );
    }

    public function testAuthRestoresTrashedShop()
    {
        // Get the shop, confirm its trashed
        $shop = Shop::withTrashed()->where('shopify_domain', 'trashed-shop.myshopify.com')->first();
        $this->assertTrue($shop->trashed());

        // Do an auth call
        $this->call('get', '/authenticate', $this->hmacTrashedParams);

        // Shop should now be restored
        $shop = $shop->fresh();
        $this->assertFalse($shop->trashed());
    }

    public function testAuthAcceptsShopWithCodeAndRedirectsToHome()
    {
        $response = $this->call('get', '/authenticate', $this->hmacParams);

        $response->assertStatus(302);
        $response->assertRedirect('http://localhost');
    }

    public function testAuthAcceptsShopWithCodeAndRedirectsToLoginIfRequestIsInvalid()
    {
        // Make the HMAC invalid
        $params = $this->hmacParams;
        $params['hmac'] = 'MakeMeInvalid';

        $response = $this->call('get', '/authenticate', $params);

        $response->assertSessionHas('error');
        $response->assertStatus(302);
        $response->assertRedirect('http://localhost/login');
    }

    public function testAuthenticateDoesNotFiresJobsWhenNoConfigForThem()
    {
        // Fake the queue
        Queue::fake();

        $this->call('get', '/authenticate', $this->hmacParams);

        // No jobs should be pushed when theres no config for them
        Queue::assertNotPushed(WebhookInstaller::class);
        Queue::assertNotPushed(ScripttagInstaller::class);
    }

    public function testAuthenticateDoesFiresJobs()
    {
        // Fake the queue
        Queue::fake();

        // Create jobs
        config(['shopify-app.webhooks' => [
            [
                'topic'   => 'orders/create',
                'address' => 'https://localhost/webhooks/orders-create',
            ],
        ]]);
        config(['shopify-app.scripttags' => [
            [
                'src' => 'https://localhost/scripts/file.js',
            ],
        ]]);

        $this->call('get', '/authenticate', $this->hmacParams);

        // Jobs should be called
        Queue::assertPushed(WebhookInstaller::class);
        Queue::assertPushed(ScripttagInstaller::class);
    }

    public function testAfterAuthenticateFiresInline()
    {
        // Fake the queue
        Queue::fake();

        // Create the jobs
        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        config(['shopify-app.after_authenticate_job' => [[
            'job'    => $jobClass,
            'inline' => true,
        ]]]);

        $method = new ReflectionMethod(AuthController::class, 'afterAuthenticateJob');
        $method->setAccessible(true);
        $result = $method->invoke(new AuthController());

        // Confirm ran, but not pushed
        $this->assertTrue($result);
        Queue::assertNotPushed($jobClass); // since inline == true
    }

    public function testAfterAuthenticateFiresDispatched()
    {
        // Fake the queue
        Queue::fake();

        // Create the job
        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        config(['shopify-app.after_authenticate_job' => [[
            'job'    => $jobClass,
            'inline' => false,
        ]]]);

        $method = new ReflectionMethod(AuthController::class, 'afterAuthenticateJob');
        $method->setAccessible(true);
        $result = $method->invoke(new AuthController());

        // Confirm ran, and pushed
        $this->assertTrue($result);
        Queue::assertPushed($jobClass); // since inline == false
    }

    public function testAfterAuthenticateDoesNotFireForNoConfig()
    {
        // Fake the queue
        Queue::fake();

        // Create the jobs... blank
        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        config(['shopify-app.after_authenticate_job' => [[]]]);

        $method = new ReflectionMethod(AuthController::class, 'afterAuthenticateJob');
        $method->setAccessible(true);
        $result = $method->invoke(new AuthController());

        // Confirm no run, and not pushed
        $this->assertFalse($result);
        Queue::assertNotPushed($jobClass);
    }

    public function testAuthPassesAndRedirectsToReturnUrl()
    {
        // Set in AuthShop middleware
        session(['return_to' => 'http://localhost/orders']);

        $response = $this->call('get', '/authenticate', $this->hmacParams);

        $response->assertStatus(302);
        $response->assertRedirect('http://localhost/orders');
    }

    public function testReturnToMethod()
    {
        // Set in AuthShop middleware
        session(['return_to' => 'http://localhost/orders']);

        $method = new ReflectionMethod(AuthController::class, 'returnTo');
        $method->setAccessible(true);

        // Test with session
        $result = $method->invoke(new AuthController());
        $this->assertEquals('http://localhost/orders', $result->headers->get('location'));

        // Re-test should have no return_to session
        $result = $method->invoke(new AuthController());
        $this->assertEquals('http://localhost', $result->headers->get('location'));
    }
}
