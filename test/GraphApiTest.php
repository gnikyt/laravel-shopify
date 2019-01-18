<?php

namespace OhMyBrew;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class GraphApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Setup for phpUnit.
     *
     * @return void
     */
    public function setUp()
    {
        // Query call
        $this->query = [
            '{ shop { products(first: 1) { edges { node { handle id } } } } }',
        ];

        // Mutation call with variables
        $this->mutation = [
            'mutation collectionCreate($input: CollectionInput!) { collectionCreate(input: $input) { userErrors { field message } collection { id } } }',
            ['input' => ['title' => 'Test Collection']],
        ];
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
        $api->graph($this->query[0]);

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
        $api->graph($this->query[0]);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API password/access token required for private Shopify GraphQL calls
     *
     * Ensure API password is there for private queries
     */
    public function itShouldThrowExceptionForMissingApiPasswordOnPrivateQuery()
    {
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');
        $api->graph($this->query[0]);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Access token required for public Shopify GraphQL calls
     *
     * Ensure access token is there for public queries
     */
    public function itShouldThrowExceptionForMissingAccessTokenOnPublicQuery()
    {
        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');
        $api->graph($this->query[0]);
    }

    /**
     * @test
     *
     * Should get Guzzle response and JSON body for success
     */
    public function itShouldReturnGuzzleResponseAndJsonBodyForSuccess()
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
        $request = $api->graph($this->query[0]);
        $data = $mock->getLastRequest()->getUri()->getQuery();
        $token_header = $mock->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        // Assert the response data
        $this->assertEquals(true, is_object($request));
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
        $this->assertEquals(200, $request->response->getStatusCode());
        $this->assertEquals(false, $request->errors);
        $this->assertEquals(true, is_object($request->body));
        $this->assertEquals('gift-card', $request->body->shop->products->edges[0]->node->handle);
        $this->assertEquals('!@#', $token_header);

        // Confirm limits have been updated
        $this->assertEquals(5, $api->getApiCalls('graph', 'made'));
        $this->assertEquals(1000, $api->getApiCalls('graph', 'limit'));
        $this->assertEquals(1000 - 5, $api->getApiCalls('graph', 'left'));
        $this->assertEquals(['left' => 1000 - 5, 'made' => 5, 'limit' => 1000, 'requestedCost' => 5, 'actualCost' => 5, 'restoreRate' => 50], $api->getApiCalls('graph'));
    }

    /**
     * @test
     *
     * Should get Guzzle response and JSON body for error
     */
    public function itShouldReturnGuzzleResponseForError()
    {
        $response = new Response(
            200,
            [],
            file_get_contents(__DIR__.'/fixtures/graphql/shop_products_error.json')
        );

        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setAccessToken('!@#');

        // Fake param just to test it receives it
        $request = $api->graph($this->query[0]);
        $data = $mock->getLastRequest()->getUri()->getQuery();
        $token_header = $mock->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        // Assert the response
        $this->assertEquals(true, is_object($request));
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
        $this->assertEquals(200, $request->response->getStatusCode());
        $this->assertEquals(true, $request->errors);
        $this->assertEquals(true, is_array($request->body));
        $this->assertEquals("Field 'productz' doesn't exist on type 'Shop'", $request->body[0]->message);
        $this->assertEquals('!@#', $token_header);

        // Confirm limits have not been updated since there is no cost
        $this->assertEquals(0, $api->getApiCalls('graph', 'made'));
        $this->assertEquals(1000, $api->getApiCalls('graph', 'limit'));
        $this->assertEquals(0, $api->getApiCalls('graph', 'left'));
        $this->assertEquals(['left' => 0, 'made' => 0, 'limit' => 1000, 'restoreRate' => 50, 'requestedCost' => 0, 'actualCost' => 0], $api->getApiCalls('graph'));
    }

    /**
     * @test
     *
     * Should process query with variables
     */
    public function itShouldProcessQueryWithVariables()
    {
        $response = new Response(
            200,
            [],
            file_get_contents(__DIR__.'/fixtures/graphql/create_collection.json')
        );

        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI();
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setAccessToken('!@#');

        // Fake param just to test it receives it
        $request = $api->graph($this->mutation[0], $this->mutation[1]);
        $data = $mock->getLastRequest()->getUri()->getQuery();
        $token_header = $mock->getLastRequest()->getHeader('X-Shopify-Access-Token')[0];

        // Assert the response data
        $this->assertEquals(true, is_object($request));
        $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
        $this->assertEquals(200, $request->response->getStatusCode());
        $this->assertEquals(true, is_object($request->body));
        $this->assertEquals('gid://shopify/Collection/63171592234', $request->body->collectionCreate->collection->id);
        $this->assertEquals('!@#', $token_header);

        // Confirm limits have been updated
        $this->assertEquals(11, $api->getApiCalls('graph', 'made'));
        $this->assertEquals(1000, $api->getApiCalls('graph', 'limit'));
        $this->assertEquals(1000 - 11, $api->getApiCalls('graph', 'left'));
        $this->assertEquals(['left' => 1000 - 11, 'made' => 11, 'limit' => 1000, 'requestedCost' => 11, 'actualCost' => 11, 'restoreRate' => 50], $api->getApiCalls('graph'));
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
