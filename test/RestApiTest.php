<?php

namespace OhMyBrew\Test;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use OhMyBrew\BasicShopifyAPI;
use ReflectionClass;

class RestApiTest extends BaseTest
{
    /**
     * @test
     *
     * Checking base URL for API calls on private
     */
    public function itShouldReturnPrivateBaseUrl()
    {
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI(true);
        $mock = $this->buildClient($api, $responses);

        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');
        $api->rest('GET', '/admin/shop.json');

        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('https', $lastRequest->getScheme());
        $this->assertEquals('example.myshopify.com', $lastRequest->getHost());
        $this->assertEquals('/admin/shop.json', $lastRequest->getPath());
        $this->assertEquals('Basic '.base64_encode('123:abc'), $mock->getLastRequest()->getHeaderLine('Authorization'));
    }

    /**
     * @test
     *
     * Checking base URL for API calls on public
     */
    public function itShouldReturnPublicBaseUrl()
    {
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $this->assertNull($request->link);
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
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                '{}'
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $responses = [new Response(200, [], '{}')];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                '{}'
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                '{}'
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

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

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, [$response, $response]);

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

    /**
     * @test
     *
     * Should catch client exception and handle it.
     */
    public function itShouldCatchClientException()
    {
        // Fake a bad response
        $responses = [
            new RequestException(
                '404 Not Found',
                new Request('GET', 'test'),
                new Response(
                    404,
                    ['http_x_shopify_shop_api_call_limit' => '2/80'],
                    file_get_contents(__DIR__.'/fixtures/rest/admin__shop_oops.json')
                )
            ),
        ];

        $api = new BasicShopifyAPI(true);
        $mock = $this->buildClient($api, $responses);

        // Make the call
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');

        // Bad route
        $result = $api->rest('GET', '/admin/shop-oops.json');

        // Confirm
        $this->assertTrue($result->errors);
        $this->assertEquals($result->body, 'Not Found');
    }

    /**
     * @test
     *
     * Should version API paths if a version is set.
     */
    public function itShouldVersionApiPaths()
    {
        $responses = [];
        for ($i = 0; $i < 4; $i++) {
            $responses[] = new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            );
        }

        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');
        $mock = $this->buildClient($api, $responses);

        // No version set
        $api->rest('GET', '/admin/shop.json');
        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('/admin/shop.json', $lastRequest->getPath());

        // A set version
        $api->setVersion('2020-01');
        $api->rest('GET', '/admin/shop.json');
        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('/admin/api/2020-01/shop.json', $lastRequest->getPath());

        // A set version already in the request path
        $api->setVersion('2020-01');
        $api->rest('GET', '/admin/api/unstable/shop.json');
        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('/admin/api/unstable/shop.json', $lastRequest->getPath());

        // Should not version
        $api->setVersion('2020-01');
        $api->rest('GET', '/admin/oauth/access_token.json');
        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('/admin/oauth/access_token.json', $lastRequest->getPath());
    }

    /**
     * @test
     *
     * Should allow for custom headers.
     */
    public function itShouldAllowForCustomHeaders()
    {
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

        $api->setShop('example.myshopify.com');
        $api->request('GET', '/admin/shop.json', null, ['X-Shopify-Test' => '123']);

        $lastRequest = $mock->getLastRequest();
        $this->assertEquals('123', $lastRequest->getHeader('X-Shopify-Test')[0]);
    }

    /**
     * @test
     *
     * Ensure Async requests are working
     */
    public function itShouldRunAsyncRequests()
    {
        $responses = [
            new Response(
                200,
                ['http_x_shopify_shop_api_call_limit' => '2/80'],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI(true);
        $mock = $this->buildClient($api, $responses);

        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');

        $promise = $api->restAsync('GET', '/admin/shop.json');
        $promise->then(function ($result) {
            $this->assertEquals(true, is_object($result->body));
            $this->assertEquals('Apple Computers', $result->body->shop->name);
        });
        $promise->wait();
    }

    /**
     * @test
     *
     * Ensures extraction of the "Link" header is done
     */
    public function itShouldExtractLinkHeader()
    {
        $pageInfo = 'eyJsYXN0X2lkIjo0MDkwMTQ0ODQ5OTgyLCJsYXN0X3ZhbHVlIjoiPGh0bWw-PGh0bWw-MiBZZWFyIERWRCwgQmx1LVJheSwgU2F0ZWxsaXRlLCBhbmQgQ2FibGUgRnVsbCBDaXJjbGXihKIgMTAwJSBWYWx1ZSBCYWNrIFByb2R1Y3QgUHJvdGVjdGlvbiB8IDIgYW4gc3VyIGxlcyBsZWN0ZXVycyBEVkQgZXQgQmx1LXJheSBldCBwYXNzZXJlbGxlcyBtdWx0aW3DqWRpYXMgYXZlYyByZW1pc2Ugw6AgMTAwICUgQ2VyY2xlIENvbXBsZXQ8c3VwPk1DPFwvc3VwPjxcL2h0bWw-PFwvaHRtbD4iLCJkaXJlY3Rpb24iOiJuZXh0In0';
        $responses = [
            new Response(
                200,
                [
                    'http_x_shopify_shop_api_call_limit' => '1/80',
                    'link'                               => '<https://example.myshopify.com/admin/api/unstable/products.json?page_info='.$pageInfo.'>; rel="next"',
                ],
                file_get_contents(__DIR__.'/fixtures/rest/admin__shop.json')
            ),
        ];

        $api = new BasicShopifyAPI(true);
        $mock = $this->buildClient($api, $responses);

        $api->setShop('example.myshopify.com');
        $api->setVersion('unstable');
        $api->setApiKey('123');
        $api->setApiPassword('abc');

        $result = $api->rest('GET', '/admin/shop.json');
        $this->assertEquals($pageInfo, $result->link->next);
    }
}
