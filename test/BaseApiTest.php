<?php

namespace OhMyBrew\Test;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;
use OhMyBrew\BasicShopifyAPI;
use Psr\Log\NullLogger;
use ReflectionClass;
use ReflectionMethod;

class BaseApiTest extends BaseTest
{
    /**
     * @test
     *
     * Should set API to private mode
     */
    public function itShouldSetApiToPrivateMode()
    {
        $api = new BasicShopifyAPI(true);

        $this->assertEquals(true, $api->isPrivate());
        $this->assertEquals(false, $api->isPublic());
    }

    /**
     * @test
     *
     * Should set API to public mode
     */
    public function itShouldSetApiToPublicMode()
    {
        $api = new BasicShopifyAPI();

        $this->assertEquals(false, $api->isPrivate());
        $this->assertEquals(true, $api->isPublic());
    }

    /**
     * @test
     *
     * Should set a logger.
     */
    public function itShouldSetLogger()
    {
        $api = new BasicShopifyAPI();

        // Confirm no logging works
        $this->assertFalse($api->log('Hello world!'));

        // Set the test logger
        $api->setLogger(new NullLogger());

        // Get the logger value
        $reflected = new ReflectionClass($api);
        $loggerProperty = $reflected->getProperty('logger');
        $loggerProperty->setAccessible(true);

        // Ensure logger value matches the test logger
        $this->assertEquals(NullLogger::class, get_class($loggerProperty->getValue($api)));

        // Confirm logging now works
        $this->assertTrue($api->log('Hello world!'));
    }

    /**
     * @test
     *
     * Should set shop
     */
    public function itShouldSetShop()
    {
        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');

        $this->assertEquals('example.myshopify.com', $api->getShop());
    }

    /**
     * @test
     *
     * Should set access token
     */
    public function itShouldSetAccessToken()
    {
        $api = new BasicShopifyAPI();
        $api->setAccessToken('123');

        $this->assertEquals('123', $api->getAccessToken());
    }

    /**
     * @test
     *
     * Ensure base URI builds.
     */
    public function itShouldReturnBaseUri()
    {
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');

        $this->assertEquals('https://example.myshopify.com', (string) $api->getBaseUri());
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage Shopify domain missing for API calls
     *
     * Ensure Shopify domain is there
     */
    public function itShouldThrowExceptionForMissingShop()
    {
        $api = new BasicShopifyAPI(true);
        $api->getBaseUri();
    }

    /**
     * @test
     *
     * Determine if request is REST or a Graph call
     */
    public function itShouldDetermineTypesOfCalls()
    {
        // Setup API
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');

        // Make the methods accessible
        $isGraphRequest = new ReflectionMethod($api, 'isGraphRequest');
        $isGraphRequest->setAccessible(true);
        $isRestRequest = new ReflectionMethod($api, 'isRestRequest');
        $isRestRequest->setAccessible(true);
        $isAuthableRequest = new ReflectionMethod($api, 'isAuthableRequest');
        $isAuthableRequest->setAccessible(true);

        // REST
        $uri = $api->getBaseUri()->withPath('/admin/shop.json');
        $this->assertFalse($isGraphRequest->invoke($api, $uri));
        $this->assertTrue($isRestRequest->invoke($api, $uri));
        $this->assertTrue($isAuthableRequest->invoke($api, $uri));

        // Graph
        $uri = $api->getBaseUri()->withPath('/admin/api/graphql.json');
        $this->assertTrue($isGraphRequest->invoke($api, $uri));
        $this->assertFalse($isRestRequest->invoke($api, $uri));
        $this->assertTrue($isAuthableRequest->invoke($api, $uri));

        // Token
        $uri = $api->getBaseUri()->withPath('/admin/oauth/access_token');
        $this->assertFalse($isGraphRequest->invoke($api, $uri));
        $this->assertTrue($isRestRequest->invoke($api, $uri));
        $this->assertFalse($isAuthableRequest->invoke($api, $uri));
    }

    /**
     * @test
     *
     * Should set API key and API password and API shared secret
     */
    public function itShouldSetApiKeyAndPassword()
    {
        $api = new BasicShopifyAPI();
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
     * Check verify with no params
     */
    public function itShouldFailRequestVerifyWithNoParams()
    {
        $api = new BasicShopifyAPI();
        $api->setApiSecret('hush');
        $this->assertEquals(false, $api->verifyRequest([]));
    }

    /**
     * @test
     *
     * @expectedException TypeError
     *
     * Check verify with no params
     */
    public function itShouldFailRequestVerifyWithNoParamsAgain()
    {
        $api = new BasicShopifyAPI();
        $api->setApiSecret('hush');
        $this->assertEquals(false, $api->verifyRequest(null));
    }

    /**
     * @test
     *
     * @expectedException Exception
     * @expectedExceptionMessage API secret is missing
     *
     * Check verify without api secret
     */
    public function itShouldThrowErrorOnVerifyWithoutApiSecret()
    {
        $api = new BasicShopifyAPI();
        $api->verifyRequest([]);
    }

    /**
     * @test
     *
     * Check verify with params
     */
    public function itShouldPassRequestVerifyWithParams()
    {
        $hmac = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
        $params = [
            'code'      => '0907a61c0c8d55e99db179b68161bc00',
            'hmac'      => $hmac,
            'shop'      => 'some-shop.myshopify.com',
            'timestamp' => '1337178173',
        ];

        $api = new BasicShopifyAPI();
        $api->setApiSecret('hush');
        $this->assertEquals(true, $api->verifyRequest($params));
    }

    /**
     * @test
     *
     * Check verify with bad params
     */
    public function itShouldPassRequestVerifyWithBadParams()
    {
        $hmac = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
        $params = [
        'code' => '0907a61c0c8d55e99db179b68161bc00-OOPS',
        'hmac' => $hmac,
        'shop' => 'some-shop.myshopify.com',
        ];

        $api = new BasicShopifyAPI();
        $api->setApiSecret('hush');
        $this->assertEquals(false, $api->verifyRequest($params));
    }

    /**
     * @test
     *
     * Should set shop and access tokeb via quick method
     */
    public function itShouldSetSession()
    {
        $api = new BasicShopifyAPI();
        $api->setSession('example.myshopify.com', '1234');

        $this->assertEquals('example.myshopify.com', $api->getShop());
        $this->assertEquals('1234', $api->getAccessToken());
    }

    /**
     * @test
     *
     * Should isolate API session
     */
    public function itShouldWithSession()
    {
        $self = $this;
        $api = new BasicShopifyAPI();

        // Isolated for a shop
        $api->withSession('example.myshopify.com', '1234', function () use (&$self) {
            $self->assertEquals('example.myshopify.com', $this->getShop());
            $self->assertEquals('1234', $this->getAccessToken());
            $self->assertInstanceOf(BasicShopifyAPI::class, $this);
        });

        // Isolated for a shop
        $api->withSession('example2.myshopify.com', '12345', function () use (&$self) {
            $self->assertEquals('example2.myshopify.com', $this->getShop());
            $self->assertEquals('12345', $this->getAccessToken());
            $self->assertInstanceOf(BasicShopifyAPI::class, $this);
        });

        // Isolated for a shop and returns a value
        $valueReturn = $api->withSession('example2.myshopify.com', '12345', function () use (&$self) {
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
    public function itShouldThrowExceptionForSessionWithNoClosure()
    {
        $api = new BasicShopifyAPI();
        $api->withSession('example.myshopify.com', '1234', null);
    }

    /**
     * @test
     *
     * Should get access and set the access
     */
    public function itShouldGetAccessFromShopify()
    {
        $responses = [
            new Response(
                200,
                [],
                file_get_contents(__DIR__.'/fixtures/admin__oauth__access_token.json')
            ),
            new Response(
                200,
                [],
                file_get_contents(__DIR__.'/fixtures/admin__oauth__access_token.json')
            ),
            new Response(
                200,
                [],
                file_get_contents(__DIR__.'/fixtures/admin__oauth__access_token__grant.json')
            ),
        ];

        $api = new BasicShopifyAPI();
        $mock = $this->buildClient($api, $responses);

        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');
        $api->setApiSecret('abc');

        // Request access
        $code = '!@#';
        $obj = $api->requestAccess($code);
        $data = json_decode($mock->getLastRequest()->getBody());

        $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $obj->access_token);
        $this->assertEquals('abc', $data->client_secret);
        $this->assertEquals('123', $data->client_id);
        $this->assertEquals($code, $data->code);

        // Request access token
        $token = $api->requestAccessToken($code);

        $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $token);

        // Request access and set
        $api->requestAndSetAccess($code);

        $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $api->getAccessToken());
        $this->assertTrue($api->hasUser());
        $this->assertEquals('john@example.com', $api->getUser()->email);
    }

    /**
     * @test
     * @expectedException Exception
     * @expectedExceptionMessage API key or secret is missing
     *
     * Ensure Shopify API secret is there for grabbing the access object
     */
    public function itShouldThrowExceptionForMissingApiSecretOnAccessRequest()
    {
        $api = new BasicShopifyAPI(true);
        $api->setShop('example.myshopify.com');
        $api->requestAccess('123');
    }

    /**
     * @test
     *
     * Should get auth URL for offline access
     */
    public function itShouldReturnAuthUrlForOffline()
    {
        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');

        $this->assertEquals(
            'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalapp.local%2F',
            $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/')
        );
    }

    /**
     * @test
     *
     * Should get auth URL for per-use access
     */
    public function itShouldReturnAuthUrlForPerUser()
    {
        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');
        $api->setApiKey('123');

        $this->assertEquals(
            'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scope=read_products%2Cwrite_products&redirect_uri=https%3A%2F%2Flocalapp.local%2F&grant_options%5B%5D=per-user',
            $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/', 'per-user')
        );
    }

    /**
     * @test
     *
     * @expectedException Exception
     * @expectedExceptionMessage API key is missing
     *
     * Should throw error for missing API key on auth call
     */
    public function itShouldThrowErrorForMissingApiKeyOnAuthCall()
    {
        $api = new BasicShopifyAPI();
        $api->setShop('example.myshopify.com');
        $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/');
    }

    /**
     * @test
     *
     * Should set rate limiting to enabled
     */
    public function itShouldSetRateLimitingToEnabled()
    {
        $api = new BasicShopifyAPI();

        $this->assertFalse($api->isRateLimitingEnabled());

        $api->enableRateLimiting(0.25 * 1000, 0);

        $reflected = new ReflectionClass($api);

        $rateLimitCycleProperty = $reflected->getProperty('rateLimitCycle');
        $rateLimitCycleProperty->setAccessible(true);

        $rateLimitCycleBufferProperty = $reflected->getProperty('rateLimitCycleBuffer');
        $rateLimitCycleBufferProperty->setAccessible(true);

        $this->assertEquals(0.25 * 1000, $rateLimitCycleProperty->getValue($api));
        $this->assertEquals(0, $rateLimitCycleBufferProperty->getValue($api));
        $this->assertTrue($api->isRateLimitingEnabled());
    }

    /**
     * @test
     *
     * Should set rate limiting to disabled
     */
    public function itShouldSetRateLimitingToDisabled()
    {
        $api = new BasicShopifyAPI();

        $api->enableRateLimiting();
        $this->assertTrue($api->isRateLimitingEnabled());

        $api->disableRateLimiting();
        $this->assertFalse($api->isRateLimitingEnabled());
    }

    /**
     * @test
     *
     * Should set and get version for API calls.
     */
    public function itShouldSetAndGetVersion()
    {
        $api = new BasicShopifyAPI(true);

        $this->assertEquals(null, $api->getVersion());

        $api->setVersion('2020-01');
        $this->assertEquals('2020-01', $api->getVersion());

        $api->setVersion('unstable');
        $this->assertEquals('unstable', $api->getVersion());
    }

    /**
     * @test
     *
     * @expectedException Exception
     * @expectedExceptionMessage Version string must be of YYYY-MM or unstable
     *
     * Should throw error for bad API version.
     */
    public function itShouldThrowErrorForBadApiVersion()
    {
        $api = new BasicShopifyAPI(true);
        $api->setVersion('01-2020');
    }
}
