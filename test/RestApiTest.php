<?php

namespace OhMyBrew;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;

class RestApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * Checking base URL for API calls on private
     */
    public function itShouldReturnPrivateBaseUrl()
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
        );
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI(true);
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');
        $api->rest('GET', '/admin/shop.json');

        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('https', $lastRequest->getScheme());
        $this->assertEquals('example.myshopify.com', $lastRequest->getHost());
        $this->assertEquals('123:abc', $lastRequest->getUserInfo());
        $this->assertEquals('/admin/shop.json', $lastRequest->getPath());
    }

    /**
     * @test
     *
     * Checking base URL for API calls on public
     */
    public function itShouldReturnPublicBaseUrl()
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
        );
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->rest('GET', '/admin/shop.json');

        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('https', $lastRequest->getScheme());
        $this->assertEquals('example.myshopify.com', $lastRequest->getHost());
        $this->assertEquals(null, $lastRequest->getUserInfo());
        $this->assertEquals('/admin/shop.json', $lastRequest->getPath());
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Shopify domain missing for API calls
     *
     * Ensure Shopify domain is there for baseURL
     */
    public function itShouldThrowExceptionForMissingDomain()
    {
        $api = new BasicShopifyAPI();
        $api->rest('GET', '/admin/shop.json');
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API key and password required for private Shopify REST calls
     *
     * Ensure Shopify API details is passsed for private API calls
     */
    public function itShouldThrowExceptionForMissingApiDetails()
    {
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');
        $api->rest('GET', '/admin/shop.json');
    }

    /**
     * @test
     *
     * Should get Guzzle response and JSON body
     */
    public function itShouldReturnGuzzleResponseAndJsonBody()
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
        );

        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');

        // Fake param just to test it receives it
        $request = $api->rest('GET', '/admin/shop.json', ['limit' => 1, 'page' => 1]);
        $data = $mock->getLastRequest()->getUri()->getQuery();
        $token_header = $mock->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        $this->assertEquals(true, is_object($request));
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
        $this->assertEquals(200, $request->response->getStatusCode());
        $this->assertEquals(true, is_object($request->body));
        $this->assertEquals('Apple Computers', $request->body->shop->name);
        $this->assertEquals('limit=1&page=1', $data);
        $this->assertEquals('!@#', $token_header);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Invalid API call limit key. Valid keys are: left, made, limit
     *
     * Ensure we pass a valid key to the API calls
     */
    public function itShouldThrowExceptionForInvalidApiCallsKey()
    {
        $api = new BasicShopifyAPI();
        $api->getApiCalls('rest', 'oops');
    }

    /**
     * @test
     *
     * Should get API call limits
     */
    public function itShouldReturnApiCallLimits()
    {
        $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->rest('GET', '/admin/shop.json');

        $this->assertEquals(2, $api->getApiCalls('rest', 'made'));
        $this->assertEquals(80, $api->getApiCalls('rest', 'limit'));
        $this->assertEquals(80 - 2, $api->getApiCalls('rest', 'left'));
        $this->assertEquals(['left' => 80 - 2, 'made' => 2, 'limit' => 80], $api->getApiCalls('rest'));
    }

    /**
     * @test
     *
     * Check if the API call limit header is missing, we do not error out
     * Example: /admin/checkout.json does not apparently return this header
     */
    public function itShouldContinueWithoutApiCallLimitHeader()
    {
        $response = new Response(200, [], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->rest('GET', '/admin/shop.json');

        $this->assertEquals(0, $api->getApiCalls('rest', 'made'));
        $this->assertEquals(40, $api->getApiCalls('rest', 'limit'));
        $this->assertEquals(0, $api->getApiCalls('rest', 'left'));
        $this->assertEquals(['left' => 0, 'made' => 0, 'limit' => 40], $api->getApiCalls('rest'));
    }

    /**
     * @test
     *
     * Should use query for GET requests
     */
    public function itShouldUseQueryForGetMethod()
    {
        $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->rest('GET', '/admin/shop.json', ['limit' => 1, 'page' => 1]);

        $this->assertEquals('limit=1&page=1', $mock->getLastRequest()->getUri()->getQuery());
        $this->assertNull(json_decode($mock->getLastRequest()->getBody()));
    }

    /**
     * @test
     *
     * Should use JSON for non-GET methods
     */
    public function itShouldUseJsonForNonGetMethods()
    {
        $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->rest('POST', '/admin/gift_cards.json', ['gift_cards' => ['initial_value' => 25.00]]);

        $this->assertEquals('', $mock->getLastRequest()->getUri()->getQuery());
        $this->assertNotNull(json_decode($mock->getLastRequest()->getBody()));
    }

    /**
     * @test
     *
     * Should alias request to REST method
     */
    public function itShouldAliasRequestToRestMethod()
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
        );
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $request = $api->request('GET', '/admin/shop.json');

        $this->assertEquals(true, is_object($request->body));
        $this->assertEquals('Apple Computers', $request->body->shop->name);
    }


    /**
     * @test
     *
     * Should track request timestamps.
     */
    public function itShouldTrackRequestTimestamps()
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
        );
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');

        $reflected = new ReflectionClass($api);
        $requestTimestampProperty = $reflected->getProperty('requestTimestamp');
        $requestTimestampProperty->setAccessible(true);

        $this->assertNull($requestTimestampProperty->getValue($api));

        $request = $api->request('GET', '/admin/shop.json');

        $this->assertNotNull($requestTimestampProperty->getValue($api));
    }

    /**
     * @test
     *
     * Should rate limit requests
     */
    public function itShouldRateLimitRequests()
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
        );
        $mock = new MockHandler([$response, $response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->enableRateLimiting();

        $timestamps = $api->request('GET', '/admin/shop.json')->timestamps;
        $timestamps2 = $api->request('GET', '/admin/shop.json')->timestamps;

        // First call should be null for initial, and greater than 0 for current
        $this->assertEquals(null, $timestamps[0]);
        $this->assertTrue($timestamps[1] > 0);

        // This call should have the last call's time for initial, and greater than 0 for current
        $this->assertEquals($timestamps[1], $timestamps2[0]);
        $this->assertTrue($timestamps2[1] > 0);
    }
}
