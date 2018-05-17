<?php

namespace OhMyBrew\ShopifyAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;

class RestApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * Checking base URL for API calls on public
     */
    public function itShouldReturnBaseUrl()
    {
        $api = new RestAPI();
        $api->setShop('example.myshopify.com');

        $this->assertEquals('https://example.myshopify.com', $api->getBaseUrl());
    }

    /**
     * @test
     *
     * Checking base URL for API calls on private
     */
    public function itShouldReturnPrivateBaseUrl()
    {
        $api = new RestAPI(true);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');

        $this->assertEquals('https://123:abc@example.myshopify.com', $api->getBaseUrl());
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API key and password required for private Shopify API calls
     *
     * Ensure Shopify API details is passsed for private API calls
     */
    public function itShouldThrowExceptionForMissingApiDetails()
    {
        $api = new RestAPI(true);
        $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/');
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API secret is missing
     *
     * Ensure Shopify API secret is there for grabbing the access tokens
     */
    public function itShouldThrowExceptionForMissingApiSecret()
    {
        $api = new RestAPI(true);
        $api->requestAccessToken('123');
    }

    /**
     * @test
     *
     * Should get auth URL
     */
    public function itShouldReturnAuthUrl()
    {
        $api = new RestAPI();
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');

        $this->assertEquals(
            'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scope=read_products,write_products&redirect_uri=https://localapp.local/',
            $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/')
        );
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

        $api = new RestAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');

        // Fake param just to test it receives it
        $request = $api->request('GET', '/admin/shop.json', ['limit' => 1, 'page' => 1]);
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
        $api = new RestAPI();
        $api->getApiCalls('oops');
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

        $api = new RestAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->request('GET', '/admin/shop.json');

        $this->assertEquals(2, $api->getApiCalls('made'));
        $this->assertEquals(80, $api->getApiCalls('limit'));
        $this->assertEquals(80 - 2, $api->getApiCalls('left'));
        $this->assertEquals(['left' => 80 - 2, 'made' => 2, 'limit' => 80], $api->getApiCalls());
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

        $api = new RestAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->request('GET', '/admin/shop.json', ['limit' => 1, 'page' => 1]);

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

        $api = new RestAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->request('POST', '/admin/gift_cards.json', ['gift_cards' => ['initial_value' => 25.00]]);

        $this->assertEquals('', $mock->getLastRequest()->getUri()->getQuery());
        $this->assertNotNull(json_decode($mock->getLastRequest()->getBody()));
    }
}
