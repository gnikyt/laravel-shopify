<?php namespace OhMyBrew;

use \ReflectionClass;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class BasicShopifyAPITest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * Should set API to private mode
     */
    function itShouldSetApiToPrivateMode() 
    {
        $api = new BasicShopifyAPI(true);
        $reflected = new ReflectionClass($api);

        $privateProperty = $reflected->getProperty('isPrivate');
        $privateProperty->setAccessible(true);

        $this->assertEquals(true, $privateProperty->getValue($api));
    }

    /**
     * @test
     *
     * Should set API to public mode
     */
    function itShouldSetApiToPublicMode() 
    {
        $api = new BasicShopifyAPI(false);
        $reflected = new ReflectionClass($api);

        $privateProperty = $reflected->getProperty('isPrivate');
        $privateProperty->setAccessible(true);

        $this->assertEquals(false, $privateProperty->getValue($api));
    }

    /**
     * @test
     *
     * Should set shop
     */
    function itShouldSetShop() 
    {
        $api = new BasicShopifyAPI;
        $api->setShop('example.myshopify.com');

        $this->assertEquals('example.myshopify.com', $api->getShop());
    }

    /**
     * @test
     *
     * Should set access token
     */
    function itShouldSetAccessToken() 
    {
        $api = new BasicShopifyAPI;
        $api->setAccessToken('123');

        $this->assertEquals('123', $api->getAccessToken());
    }

    /**
     * @test
     *
     * Should set API key and API password and API shared secret
     */
    function itShouldSetApiKeyAndPassword() 
    {
        $api = new BasicShopifyAPI;
        $api->setApiKey('123');
        $api->setApiPassword('abc');
        $api->setApiSecret('!@#');

        $reflected = new ReflectionClass($api);

        $apiKeyProperty = $reflected->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);

        $apiPasswordProperty = $reflected->getProperty('apiPassword');
        $apiPasswordProperty->setAccessible(true);

        $apiSecretProperty = $reflected->getProperty('apiSecret');
        $apiSecretProperty->setAccessible(true);

        $this->assertEquals('123', $apiKeyProperty->getValue($api));
        $this->assertEquals('abc', $apiPasswordProperty->getValue($api));
        $this->assertEquals('!@#', $apiSecretProperty->getValue($api));
    }

    /**
     * @test
     *
     * Checking base URL for API calls on public
     */
    function itShouldReturnBaseUrl() 
    {
        $api = new BasicShopifyAPI;
        $api->setShop('example.myshopify.com');

        $reflected = new ReflectionClass($api);

        $baseUrlMethod = $reflected->getMethod('getBaseUrl');
        $baseUrlMethod->setAccessible(true);

        $this->assertEquals('https://example.myshopify.com', $baseUrlMethod->invoke($api));
    }

    /**
     * @test
     *
     * Checking base URL for API calls on private
     */
    function itShouldReturnPrivateBaseUrl() 
    {
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiPassword('abc');

        $reflected = new ReflectionClass($api);

        $baseUrlMethod = $reflected->getMethod('getBaseUrl');
        $baseUrlMethod->setAccessible(true);

        $this->assertEquals('https://123:abc@example.myshopify.com', $baseUrlMethod->invoke($api));
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Shopify domain missing for API calls
     *
     * Ensure Shopify domain is passed to API
     */
    function itShouldThrowExceptionForMissingShopifyDomain() 
    {
        $api = new BasicShopifyAPI;
        $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/');
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API key and password required for private Shopify API calls
     *
     * Ensure Shopify API details is passsed for private API calls
     */
    function itShouldThrowExceptionForMissingApiDetails() 
    {
        $api = new BasicShopifyAPI(true);
        $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/');
    }

    /**
     * @test
     *
     * Should allow for own client injection
     */
    function itShouldAllowForOwnClient() 
    {
        $api = new BasicShopifyAPI;
        $api->setClient(new Client(['handler' => new MockHandler]));

        $reflected = new ReflectionClass($api);

        $clientProperty = $reflected->getProperty('client');
        $clientProperty->setAccessible(true);

        $value = $clientProperty->getValue($api);

        $this->assertEquals('GuzzleHttp\Handler\MockHandler', get_class($value->getConfig('handler')));
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API secret is missing
     *
     * Ensure Shopify API secret is there for grabbing the access tokens
     */
    function itShouldThrowExceptionForMissingApiSecret() 
    {
        $api = new BasicShopifyAPI(true);
        $api->requestAccessToken('123');
    }

    /**
     * @test
     *
     * Should get access token from Shopify
     */
    function itShouldGetAccessTokenFromShopify() 
    {
        $response = new Response(
            200,
            [],
            file_get_contents(__DIR__.'/fixtures/admin__oauth__access_token.json')
        );

        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI;
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiSecret('abc');
        $api->setClient($client);

        $code = '!@#';
        $token = $api->requestAccessToken($code);
        $data = json_decode($mock->getLastRequest()->getBody());

        $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $token);
        $this->assertEquals('abc', $data->client_secret);
        $this->assertEquals('123', $data->client_id);
        $this->assertEquals($code, $data->code);
    }

    /**
     * @test
     *
     * Should get auth URL
     */
    function itShouldReturnAuthUrl() 
    {
        $api = new BasicShopifyAPI;
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
     * Check verify with no params
     */
    function itShouldFailRequestVerifyWithNoParams() 
    {
        $api = new BasicShopifyAPI;
        $this->assertEquals(false, $api->verifyRequest([]));
    }

    /**
     * @test
     *
     * @expectedException TypeError
     *
     * Check verify with no params
     */
    function itShouldFailRequestVerifyWithNoParamsAgain() 
    {
        $api = new BasicShopifyAPI;
        $this->assertEquals(false, $api->verifyRequest(null));
    }

    /**
     * @test
     *
     * Check verify with params
     */
    function itShouldPassRequestVerifyWithParams() 
    {
        $hmac = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
        $params = [
            'code' => '0907a61c0c8d55e99db179b68161bc00',
            'hmac' => $hmac,
            'shop' => 'some-shop.myshopify.com',
            'timestamp' => '1337178173'
        ];

        $api = new BasicShopifyAPI;
        $api->setApiSecret('hush');
        $this->assertEquals(true, $api->verifyRequest($params));
    }

    /**
     * @test
     *
     * Check verify with bad params
     */
    function itShouldPassRequestVerifyWithBadParams() 
    {
        $hmac = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
        $params = [
        'code' => '0907a61c0c8d55e99db179b68161bc00-OOPS',
        'hmac' => $hmac,
        'shop' => 'some-shop.myshopify.com'
        ];

        $api = new BasicShopifyAPI;
        $api->setApiSecret('hush');
        $this->assertEquals(false, $api->verifyRequest($params));
    }

    /**
     * @test
     *
     * Should get Guzzle response and JSON body
     */
    function itShouldReturnGuzzleResponseAndJsonBody() 
    {
        $response = new Response(
            200,
            ['http_x_shopify_shop_api_call_limit' => '2/80'],
            file_get_contents(__DIR__.'/fixtures/admin__shop.json')
        );

        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI;
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
    function itShouldThrowExceptionForInvalidApiCallsKey() 
    {
        $api = new BasicShopifyAPI;
        $api->getApiCalls('oops');
    }

    /**
     * @test
     *
     * Should get API call limits
     */
    function itShouldReturnApiCallLimits() 
    {
        $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI;
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
     * Should set shop and access tokeb via quick method
     */
    function itShouldSetSession()
    {
        $api = new BasicShopifyAPI;
        $api->setSession('example.myshopify.com', '1234');

        $this->assertEquals('example.myshopify.com', $api->getShop());
        $this->assertEquals('1234', $api->getAccessToken());
    }

    /**
     * @test
     *
     * Should isolate API session
     */
    function itShouldWithSession()
    {
        $self = $this;
        $api = new BasicShopifyAPI;

        // Isolated for a shop
        $api->withSession('example.myshopify.com', '1234', function() use(&$self) {
            $self->assertEquals('example.myshopify.com', $this->getShop());
            $self->assertEquals('1234', $this->getAccessToken());
        });

        // Isolated for a shop
        $api->withSession('example2.myshopify.com', '12345', function() use(&$self) {
            $self->assertEquals('example2.myshopify.com', $this->getShop());
            $self->assertEquals('12345', $this->getAccessToken());
        });

        // Isolated for a shop and returns a value
        $valueReturn = $api->withSession('example2.myshopify.com', '12345', function() use(&$self) {
            return $this->getAccessToken();
        });
        $this->assertEquals($valueReturn, '12345');

        // Should remain untouched
        $this->assertEquals($api->getShop(), null);
        $this->assertEquals($api->getAccessToken(), null);

    }

    /**
     * @test
     * @expectedException TypeError
     *
     * Ensure a closure is passed to withSession
     */
    function itShouldThrowExceptionForSessionWithNoClosure() 
    {
        $api = new BasicShopifyAPI;
        $api->withSession('example.myshopify.com', '1234', null);
    }

    /**
     * @test
     *
     * Should use query for GET requests
     */
    function itShouldUseQueryForGetMethod() 
    {
        $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI;
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
    function itShouldUseJsonForNonGetMethods() 
    {
        $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
        $mock = new MockHandler([$response]);
        $client = new Client(['handler' => $mock]);

        $api = new BasicShopifyAPI;
        $api->setClient($client);
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setAccessToken('!@#');
        $api->request('POST', '/admin/gift_cards.json', ['gift_cards' => ['initial_value' => 25.00]]);

        $this->assertEquals('', $mock->getLastRequest()->getUri()->getQuery());
        $this->assertNotNull(json_decode($mock->getLastRequest()->getBody()));
    }
}
