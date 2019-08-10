<?php

namespace OhMyBrew\ShopifyApp\Test\Middleware;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Middleware\AuthShop;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AuthShopMiddlewareTest extends TestCase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to get shop domain.
     */
    public function testShopHasNoDomainShouldAbort()
    {
        // Run the middleware
        $result = $this->runAuthShop();
    }

    public function testShopHasWithAccessShouldPassMiddleware()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called
        $this->assertTrue($result[1]);
    }

    public function testShopWithNoTokenShouldNotPassMiddleware()
    {
        // Set a shop
        $shop = factory(Shop::class)->create([
            'shopify_token' => null,
        ]);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopTrashedShouldNotPassMiddleware()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        $shop->delete();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called a redirect happens
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);
    }

    public function testShopsWhichDoNotMatchShouldKillSessionAndDirectToReAuthenticate()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Go in as a new shop
        Input::merge(['shop' => 'example-different-shop.myshopify.com']);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and the new shop was passed
        $this->assertFalse($result[1]);
        $this->assertEquals('example-different-shop.myshopify.com', Request::get('shop'));
    }

    public function testGrantTypePerUserWithInvalidSessionShouldDirectToReAuthenticate()
    {
        // Update config to be per-user
        Config::set('shopify-app.api_grant_mode', 'per-user');

        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and the new shop was passed
        $this->assertFalse($result[1]);
    }

    public function testShouldSaveReturnUrl()
    {
        // Set a shop
        $shop = factory(Shop::class)->create([
            'shopify_token' => null,
        ]);
        Session::put('shopify_domain', $shop->shopify_domain);

        // Duplicate the request so we can mod the request URI
        $currentRequest = Request::instance();
        Request::swap($currentRequest->duplicate(null, null, null, null, null, array_merge(Request::server(), ['REQUEST_URI' => '/orders'])));

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertEquals('http://localhost/orders', Session::get('return_to'));
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);

        // Reset
        // Request::swap($currentRequest);
    }

    public function testShopWithGetShouldLoadGetDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            ['shop' => 'queryshop.myshopify.com'],
            // Request Params
            null,
            // Attributes
            null,
            // Cookies
            null,
            // Files
            null,
            // Server vars
            // This should be ignored as there is a get variable
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com&hmac=a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163&timestamp=1337178173&code=1234678',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=queryshop.myshopify.com') !== false);
    }

    public function testShopHasWithRefererShouldLoadRefererDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'adsadda');

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
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com&hmac=a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163&timestamp=1337178173&code=1234678',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the referer
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example.myshopify.com') !== false);
    }

    public function testShopHasWithMissingRefererDetailsShouldLoadSessionDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'sessionz');

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
            // Intentionally bad hmac
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=sessionz.myshopify.com') !== false);
    }

    public function testShopHasWithBadRefererHmacShouldLoadSessionDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'adsadda');

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
            // Intentionally bad hmac
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com&hmac=XXXXXXX&timestamp=1337178173&code=1234678',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=adsadda.myshopify.com') !== false);
    }



    private function runAuthShop(Closure $cb = null, $requestInstance = null)
    {
        $called = false;
        $response = (new AuthShop())->handle($requestInstance ? $requestInstance : Request::instance(), function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return [$response, $called];
    }
}
