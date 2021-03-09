<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Exceptions\MissingShopDomainException;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Http\Middleware\AuthShopify as AuthShopifyMiddleware;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Test\TestCase;

class AuthShopifyTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ShopSession
     */
    protected $shopSession;

    public function setUp(): void
    {
        parent::setUp();

        $this->shopSession = $this->app->make(ShopSession::class);
    }

    public function testQueryInput(): void
    {
        // Create the shop
        factory($this->model)->create(['name' => 'mystore123.myshopify.com']);

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => '3d9768c9cc44b8bd66125cb82b6a59a3d835432f560d19b3f79b9fc696ef6396',
                'timestamp' => '1565631587',
                'code'      => '123',
                'locale'    => 'de',
                'state'     => '3.14',
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=xyz.com',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuth();
        $this->assertTrue($result);
    }

    public function testHmacFail(): void
    {
        $this->expectException(SignatureVerificationException::class);

        // Create the shop
        factory($this->model)->create(['name' => 'mystore123.myshopify.com']);

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => '9f4d79eb5ab1806c390b3dda0bfc7be714a92df165d878f22cf3cc8145249ca8',
                'timestamp' => 'oops',
                'code'      => 'oops',
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // This valid referer should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=xyz.com',
            ])
        );

        Request::swap($newRequest);

        $this->runAuth();
    }

    public function testReferer(): void
    {
        // Create the shop
        factory($this->model)->create(['name' => 'example.myshopify.com']);

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            null,
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com&hmac=6f16da24e8185e717f22a3373a1928fcaea7ea2401be40ab0d160f5bed7fe55a&timestamp=1337178173&code=1234678',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuth();
        $this->assertTrue($result);
    }

    public function testHeaders(): void
    {
        // Create the shop
        factory($this->model)->create(['name' => 'example.myshopify.com']);

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            null,
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // Referer with no query params
            array_merge(Request::server(), [
                'Referer' => '',
            ])
        );

        $newRequest->headers->set('X-Shop-Domain', 'example.myshopify.com');
        $newRequest->headers->set('X-Shop-Signature', '6f16da24e8185e717f22a3373a1928fcaea7ea2401be40ab0d160f5bed7fe55a');
        $newRequest->headers->set('X-Shop-Time', '1337178173');
        $newRequest->headers->set('X-Shop-Code', '1234678');

        Request::swap($newRequest);

        $result = $this->runAuth();
        $this->assertTrue($result);
    }

    public function testLoginShopThatsInvalid(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Now, remove its token to make it invalid
        $shop->password = '';
        $shop->save();
        $shop->refresh();

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop' => $shop->getDomain()->toNative(),
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            Request::server()
        );

        Request::swap($newRequest);

        // Now, invalidation should cause redirect
        $result = $this->runAuth();
        $this->assertFalse($result);
    }

    public function testShopifySessionTokenInvalid(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Set a session token
        $this->shopSession->setSessionToken('123abc');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'    => $shop->getDomain()->toNative(),
                'session' => 'xyz123', // Different here
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            Request::server()
        );

        Request::swap($newRequest);

        // Now, invalidation should cause redirect
        $result = $this->runAuth();
        $this->assertFalse($result);
    }

    public function testShopifySessionTokenValid(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Set a session token and login shop
        $this->shopSession->setSessionToken('123abc');
        $this->shopSession->make($shop->getDomain());

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'    => $shop->getDomain()->toNative(),
                'session' => '123abc', // Same
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            Request::server()
        );

        Request::swap($newRequest);

        // Now, invalidation should cause redirect
        $result = $this->runAuth();
        $this->assertTrue($result);
    }

    public function testLoginShopWithoutShopDomain(): void
    {
        $this->expectException(MissingShopDomainException::class);

        // Now, invalidation should cause redirect
        $this->runAuth();
    }

    public function testLoginShopWithExistingSession(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Log the shop in before running the middleware
        $this->shopSession->make($shop->getDomain());

        $result = $this->runAuth();
        $this->assertTrue($result);
    }

    public function testLoginShopWithExistingSessionClashes(): void
    {
        // Create the shop
        $shop = factory($this->model)->create();

        // Log the shop in before running the middleware
        $this->shopSession->make($shop->getDomain());

        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop' => 'conflict-shop.myshopify.com',
            ],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            Request::server()
        );

        Request::swap($newRequest);

        $result = $this->runAuth();
        $this->assertFalse($result);
    }

    private function runAuth(Closure $cb = null, $requestInstance = null): bool
    {
        $called = false;
        ($this->app->make(AuthShopifyMiddleware::class))->handle($requestInstance ? $requestInstance : Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return $called;
    }
}
