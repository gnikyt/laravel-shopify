<?php

namespace OhMyBrew\ShopifyAPI;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use Exception;

class GraphApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * Checking base URL for API calls
     */
    public function itShouldReturnBaseUrl()
    {
        $api = new GraphAPI();
        $api->setShop('example.myshopify.com');

        $this->assertEquals('https://example.myshopify.com', $api->getBaseUrl());
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
        $api = new GraphAPI(true);
        $api->requestAccessToken('123');
    }

    /**
     * @test
     *
     * Should get auth URL
     */
    public function itShouldReturnAuthUrl()
    {
        $api = new GraphAPI();
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');

        $this->assertEquals(
            'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scope=read_products,write_products&redirect_uri=https://localapp.local/',
            $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/')
        );
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API password required for Shopify GraphQL calls
     *
     * Ensure Shopify API password is there for private calls
     */
    public function itShouldThrowExceptionForMissingApiPasswordOnPrivate()
    {
        $api = new GraphAPI(true);
        $api->request('{}');
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Access token required for Shopify GraphQL calls
     *
     * Ensure Shopify API password is there for private calls
     */
    public function itShouldThrowExceptionForMissingAccessTokenOnPublic()
    {
        $api = new GraphAPI();
        $api->request('{}');
    }

    /**
     * @test
     *
     * Should get Guzzle response and JSON body
     */
    public function itShouldReturnGuzzleResponseAndJsonBodyWithApiCallLimits()
    {
        $response = new Response(
            200,
            [],
            file_get_contents(__DIR__.'/fixtures/graphql/shop_products.json')
        );

        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new GraphAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setAccessToken('!@#');

        $query =<<<QL
{
    shop {
        products(first: 2) {
            edges {
                node {
                    id
                    handle
                }
            }
        }
    }
}
QL;

        // Fake param just to test it receives it
        $request = $api->request($query);
        $data = $mock->getLastRequest()->getUri()->getQuery();
        $token_header = $mock->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        $this->assertEquals(true, is_object($request));
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
        $this->assertEquals(200, $request->response->getStatusCode());
        $this->assertEquals(true, is_object($request->body));
        $this->assertEquals('gift-card', $request->body->shop->products->edges[0]->node->handle);
        $this->assertEquals('!@#', $token_header);

        $this->assertEquals(5, $api->getApiCalls('made'));
        $this->assertEquals(1000, $api->getApiCalls('limit'));
        $this->assertEquals(1000 - 5, $api->getApiCalls('left'));
        $this->assertEquals(['left' => 1000 - 5, 'made' => 5, 'limit' => 1000, 'requestedCost' => 5, 'actualCost' => 5, 'restoreRate' => 50], $api->getApiCalls());
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Invalid API call limit key. Valid keys are: left, made, limit, restoreRate, requestedCost, actualCost
     *
     * Ensure we pass a valid key to the API calls
     */
    public function itShouldThrowExceptionForInvalidApiCallsKey()
    {
        $api = new GraphAPI();
        $api->getApiCalls('oops');
    }
}
