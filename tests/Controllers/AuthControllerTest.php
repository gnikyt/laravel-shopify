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

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub()]);
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
        $this->assertEquals('http://localhost/login', $response->headers->get('location'));
    }

    public function testAuthRedirectsUserToAuthScreenWhenNoCode()
    {
        $this->assertEquals(false, config('session.expire_on_close')); // Default for Laravel

        $response = $this->post('/authenticate', ['shop' => 'example.myshopify.com']);
        $response->assertSessionHas('shopify_domain');
        $response->assertViewHas('shopDomain', 'example.myshopify.com');
        $response->assertViewHas(
            'authUrl',
            'https://example.myshopify.com/admin/oauth/authorize?client_id=&scope=read_products,write_products&redirect_uri=https://localhost/authenticate'
        );

        $this->assertEquals(true, config('session.expire_on_close')); // Override in auth for a single request
    }

    public function testAuthAcceptsShopWithCodeAndUpdatesTokenForShop()
    {
        $response = $this->call('get', '/authenticate', $this->hmacParams);

        $shop = Shop::where('shopify_domain', 'example.myshopify.com')->first();
        $this->assertEquals('12345678', $shop->shopify_token); // Previous token was 1234
    }

    public function testAuthRestoresTrashedShop()
    {
        $shop = Shop::withTrashed()->where('shopify_domain', 'trashed-shop.myshopify.com')->first();
        $this->assertTrue($shop->trashed());

        $this->call('get', '/authenticate', $this->hmacTrashedParams);

        $shop = $shop->fresh();
        $this->assertFalse($shop->trashed());
    }

    public function testAuthAcceptsShopWithCodeAndRedirectsToHome()
    {
        $response = $this->call('get', '/authenticate', $this->hmacParams);

        $response->assertStatus(302);
        $this->assertEquals('http://localhost', $response->headers->get('location'));
    }

    public function testAuthAcceptsShopWithCodeAndRedirectsToLoginIfRequestIsInvalid()
    {
        $params = $this->hmacParams;
        $params['hmac'] = 'MakeMeInvalid';

        $response = $this->call('get', '/authenticate', $params);

        $response->assertSessionHas('error');
        $response->assertStatus(302);
        $this->assertEquals('http://localhost/login', $response->headers->get('location'));
    }

    public function testAuthenticateDoesNotFiresJobsWhenNoConfigForThem()
    {
        Queue::fake();

        $this->call('get', '/authenticate', $this->hmacParams);

        Queue::assertNotPushed(WebhookInstaller::class);
        Queue::assertNotPushed(ScripttagInstaller::class);
    }

    public function testAuthenticateDoesFiresJobs()
    {
        Queue::fake();

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

        Queue::assertPushed(WebhookInstaller::class);
        Queue::assertPushed(ScripttagInstaller::class);
    }

    public function testAfterAuthenticateFiresInline()
    {
        Queue::fake();

        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        config(['shopify-app.after_authenticate_job' => [
            'job'    => $jobClass,
            'inline' => true,
        ]]);

        $method = new ReflectionMethod(AuthController::class, 'afterAuthenticateJob');
        $method->setAccessible(true);
        $result = $method->invoke(new AuthController());

        $this->assertEquals(true, $result);
        Queue::assertNotPushed($jobClass); // since inline == true
    }

    public function testAfterAuthenticateFiresDispatched()
    {
        Queue::fake();

        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        config(['shopify-app.after_authenticate_job' => [
            'job'    => $jobClass,
            'inline' => false,
        ]]);

        $method = new ReflectionMethod(AuthController::class, 'afterAuthenticateJob');
        $method->setAccessible(true);
        $result = $method->invoke(new AuthController());

        $this->assertEquals(true, $result);
        Queue::assertPushed($jobClass); // since inline == false
    }

    public function testAfterAuthenticateDoesNotFireForNoConfig()
    {
        Queue::fake();

        $jobClass = \App\Jobs\AfterAuthenticateJob::class;
        config(['shopify-app.after_authenticate_job' => []]);

        $method = new ReflectionMethod(AuthController::class, 'afterAuthenticateJob');
        $method->setAccessible(true);
        $result = $method->invoke(new AuthController());

        $this->assertEquals(false, $result);
        Queue::assertNotPushed($jobClass);
    }
}
