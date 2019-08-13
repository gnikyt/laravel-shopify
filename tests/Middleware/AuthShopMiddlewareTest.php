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

    public function testValidShopsWhichDoNotMatchShouldKillSessionAndDirectToReAuthenticate()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        Session::put('shopify_domain', $shop->shopify_domain);

        // Go in as a new shop
        Input::merge([
            'shop'      => 'example.myshopify.com',
            'hmac'      => 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163',
            'timestamp' => '1337178173',
            'code'      => '1234678',
        ]);

        // Run the middleware
        $result = $this->runAuthShop();

        // Assert it was not called and the new shop was passed
        $this->assertNull(Session::get('shopify_domain'));
        $this->assertFalse($result[1]);
        $this->assertEquals('example.myshopify.com', Request::get('shop'));
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
        $this->assertNull(Session::get('shopify_domain'));
        $this->assertFalse($result[1]);
        $this->assertEquals('http://localhost/orders', Session::get('return_to'));
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate') !== false);

        // Reset
        // Request::swap($currentRequest);
    }

    public function testShopWithAllValidGetShouldLoadGetDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore123.myshopify.com',
                'hmac'      => '9f4d79eb5ab1806c390b3dda0bfc7be714a92df165d878f22cf3cc8145249ca8',
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

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=mystore123.myshopify.com') !== false);
    }

    public function testShopWithValidGetNoCodeShouldLoadGetDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore01.myshopify.com',
                'hmac'      => '1b8e7d49308155d164ba3768e9f4f16dca412a9c29e049fa0d76d995b5432ba7',
                'timestamp' => '1565631587',
                'locale'    => 'de',
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

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=mystore01.myshopify.com') !== false);
    }

    public function testShopWithValidGetNoCodeNoLocaleShouldLoadGetDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore02.myshopify.com',
                'hmac'      => '0c5789783621d5c31f19a66cc628441786d681f2de4b50dd0a0a8a849d00abfe',
                'timestamp' => '1565631887',
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

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=mystore02.myshopify.com') !== false);
    }

    public function testShopWithValidGetNoCodeNoLocaleWithStateShouldLoadGetDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'mystore03.myshopify.com',
                'hmac'      => '0a5207d2c73f09e66da51e7df47d5aeba9809ece6b1d7baa5daf7b7bfdaf0432',
                'timestamp' => '1565631987',
                'state'     => '6.62607004',
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

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=mystore03.myshopify.com') !== false);
    }


    public function testShopWithValidGetWithCodeShouldLoadGetDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'example.myshopify.com',
                'hmac'      => 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163',
                'timestamp' => '1337178173',
                'code'      => '1234678',
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

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example.myshopify.com') !== false);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to verify signature.
     */
    public function testShopWithInvalidGetShouldFail()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // this should get ignored as there is a get variable
        Session::put('shopify_domain', 'adsadda');

        // Run the middleware
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop'      => 'example.myshopify.com',
                'hmac'      => 'XXXXX',
                'timestamp' => '1337178173',
                'code'      => '1234678',
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

        $result = $this->runAuthShop();
    }

    public function testShopWithValidRefererShouldLoadRefererDomain()
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

    public function testShopWithValidRefererNoCodeShouldLoadRefererDomain()
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
                'HTTP_REFERER' => 'https://xxx.com?shop=example.myshopify.com&hmac=53c2802b141a564dc1992e4c468def31391c0ad2a7172ee23ff1493c879c55ba&timestamp=1337178173',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the referer
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example.myshopify.com') !== false);
    }

    public function testShopWithValidRefererNoCodeWithLocaleShouldLoadRefererDomain()
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
                'HTTP_REFERER' => 'https://xxx.com?shop=example2.myshopify.com&hmac=9dfc3cd16c21f683a30a69921678c66382884bc6c25704c130c7ea1286cabeff&timestamp=1337188173&locale=de',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the referer
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example2.myshopify.com') !== false);
    }

    public function testShopWithValidRefererNoCodeNoLocaleShouldLoadRefererDomain()
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
                'HTTP_REFERER' => 'https://xxx.com?shop=example123.myshopify.com&hmac=e3ff572343356e923fddbec02a4720190469c6243584088d6da1b87f474d652b&timestamp=1337188173&state=0.12345678',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the referer
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example123.myshopify.com') !== false);
    }

    public function testShopWithValidRefererAllParamsShouldLoadRefererDomain()
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
                'HTTP_REFERER' => 'https://xxx.com?shop=example3.myshopify.com&hmac=25d780440c3aecf332c946fb9cc222534113c8a5de9c28976e37754aa0f46ba7&timestamp=1337188173&locale=de&code=5678&state=0.1234',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the referer
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example3.myshopify.com') !== false);
    }

    public function testShopWithMissingRefererDetailsShouldLoadSessionDomain()
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

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to verify signature.
     */
    public function testShopWithBadRefererHmacShouldLoadSessionDomain()
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

        // Should throw exception
        $result = $this->runAuthShop();
    }

    public function testShopWithEmptyRefererShouldLoadSessionDomain()
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
            // Referer with no query params
            array_merge(Request::server(), [
                'HTTP_REFERER' => 'https://xxx.com',
            ])
        );

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=adsadda.myshopify.com') !== false);
    }

    public function testShopWithValidShopHeadersAndCodeShouldLoadHeaderDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'xxxaaa');

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
        $newRequest->headers->set('X-Shop-Signature', 'a7448f7c42c9bc025b077ac8b73e7600b6f8012719d21cbeb88db66e5dbbd163');
        $newRequest->headers->set('X-Shop-Time', '1337178173');
        $newRequest->headers->set('X-Shop-Code', '1234678');

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example.myshopify.com') !== false);
    }

    public function testShopWithAllValidShopHeadersShouldLoadHeaderDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'xxxaaa');

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

        $newRequest->headers->set('X-Shop-Domain', 'example007.myshopify.com');
        $newRequest->headers->set('X-Shop-Signature', '609808dd12f1c464ce297821fe7ffbfe72cb51eac0944d1171e0db54c9846519');
        $newRequest->headers->set('X-Shop-Time', '1337179173');
        $newRequest->headers->set('X-Shop-Code', '123123123');
        $newRequest->headers->set('X-Shop-Locale', 'es');
        $newRequest->headers->set('X-Shop-State', '0.98765');

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example007.myshopify.com') !== false);
    }

    public function testShopWithMinimumValidShopHeadersShouldLoadHeaderDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'xxxaaa');

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

        $newRequest->headers->set('X-Shop-Domain', 'example008.myshopify.com');
        $newRequest->headers->set('X-Shop-Signature', '046edaedd7e1fc57bb433d8251dfcd17351e2600d872881b9ff3644fdac3eb24');
        $newRequest->headers->set('X-Shop-Time', '1337179173');

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example008.myshopify.com') !== false);
    }

    public function testShopWithValidShopHeadersAndLocaleShouldLoadHeaderDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'xxxaaa');

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

        $newRequest->headers->set('X-Shop-Domain', 'example009.myshopify.com');
        $newRequest->headers->set('X-Shop-Signature', '6ed241365754634f8ee630d115e24a22e4cca33dba92f45506da27b789cd1e1b');
        $newRequest->headers->set('X-Shop-Time', '1337179173');
        $newRequest->headers->set('X-Shop-Locale', 'gb');

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example009.myshopify.com') !== false);
    }

    public function testShopWithValidShopHeadersAndStateShouldLoadHeaderDomain()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'xxxaaa');

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

        $newRequest->headers->set('X-Shop-Domain', 'example010.myshopify.com');
        $newRequest->headers->set('X-Shop-Signature', 'af846cf75da1f12e97785d66d9340c877a65e86db44536c366476c30e34a57e8');
        $newRequest->headers->set('X-Shop-Time', '1337179193');
        $newRequest->headers->set('X-Shop-State', '0.555444333222111');

        Request::swap($newRequest);

        $result = $this->runAuthShop();

        // Assert it was not called and a redirect happened
        $this->assertFalse($result[1]);
        // Make sure it's the one in the session
        $this->assertTrue(strpos($result[0], 'Redirecting to http://localhost/authenticate/full?shop=example010.myshopify.com') !== false);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unable to verify signature.
     */
    public function testShopWithInvalidShopHeadersShouldFail()
    {
        // Set a shop
        $shop = factory(Shop::class)->create();
        // This should be ignored as there is a referer domain
        Session::put('shopify_domain', 'xxxaaa');

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

        $newRequest->headers->set('X-Shop-Domain', 'example.com');
        $newRequest->headers->set('X-Shop-Signature', 'XXXXXXXX');
        $newRequest->headers->set('X-Shop-Time', '123');

        Request::swap($newRequest);

        // An exception should be thrown. See docblock.
        $result = $this->runAuthShop();
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
