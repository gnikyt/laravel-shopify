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
     * Checking base URL for API calls on public
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
     *
     * Should get Guzzle response and JSON body
     */
    public function itShouldReturnGuzzleResponseAndJsonBody()
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
    }
}
