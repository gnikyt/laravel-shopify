<?php

namespace OhMyBrew;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class GraphApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Setup for phpUnit
     *
     * @return void
     */
    public function setUp() {
        $this->query = <<<'QL'
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
    }

    /**
     * @test
     *
     * Checking base URL
     */
    public function itShouldReturnBaseUrl()
    {
        $response = new Response(
            200,
            [],
            file_get_contents(__DIR__.'/fixtures/graphql/shop_products.json')
        );
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI(true);
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');
        $api->graph($this->query);

        $lastRequest = $mock->getLastRequest()->getUri();
        $this->assertEquals('https', $lastRequest->getScheme());
        $this->assertEquals('example.myshopify.com', $lastRequest->getHost());
        $this->assertEquals('/admin/api/graphql.json', $lastRequest->getPath());
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Shopify domain missing for API calls
     *
     * Ensure Shopify domain is there for queries
     */
    public function itShouldThrowExceptionForMissingDomainOnQuery()
    {
        $api = new BasicShopifyAPI();
        $api->graph($this->query);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API password required for Shopify GraphQL calls
     *
     * Ensure API password is there for private queries
     */
    public function itShouldThrowExceptionForMissingApiPasswordOnPrivateQuery()
    {
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');
        $api->graph($this->query);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Access token required for Shopify GraphQL calls
     *
     * Ensure access token is there for public queries
     */
    public function itShouldThrowExceptionForMissingAccessTokenOnPublicQuery()
    {
        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');
        $api->graph($this->query);
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

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setAccessToken('!@#');

        // Fake param just to test it receives it
        $request = $api->graph($this->query);
        $data = $mock->getLastRequest()->getUri()->getQuery();
        $token_header = $mock->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        $this->assertEquals(true, is_object($request));
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
        $this->assertEquals(200, $request->response->getStatusCode());
        $this->assertEquals(true, is_object($request->body));
        $this->assertEquals('gift-card', $request->body->shop->products->edges[0]->node->handle);
        $this->assertEquals('!@#', $token_header);

        $this->assertEquals(5, $api->getApiCalls('graph', 'made'));
        $this->assertEquals(1000, $api->getApiCalls('graph', 'limit'));
        $this->assertEquals(1000 - 5, $api->getApiCalls('graph', 'left'));
        $this->assertEquals(['left' => 1000 - 5, 'made' => 5, 'limit' => 1000, 'requestedCost' => 5, 'actualCost' => 5, 'restoreRate' => 50], $api->getApiCalls('graph'));
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
        $api = new BasicShopifyAPI();
        $api->getApiCalls('graph', 'oops');
    }
}
