<?php
namespace TylerKing;

use \ReflectionClass;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;

class BasicShopifyAPITest extends \PHPUnit_Framework_TestCase
{
  /**
   * @test
   *
   * Should set API to private mode
   */
  function itShouldSetApiToPrivateMode() {
    $api       = new BasicShopifyAPI(true);
    $reflected = new ReflectionClass($api);

    $private_property = $reflected->getProperty('is_private');
    $private_property->setAccessible(true);

    $this->assertEquals(true, $private_property->getValue($api));
  }

  /**
   * @test
   *
   * Should set API to public mode
   */
  function itShouldSetApiToPublicMode() {
    $api       = new BasicShopifyAPI(false);
    $reflected = new ReflectionClass($api);

    $private_property = $reflected->getProperty('is_private');
    $private_property->setAccessible(true);

    $this->assertEquals(false, $private_property->getValue($api));
  }

  /**
  * @test
  *
  * Should set shop
  */
  function itShouldSetShop() {
    $api = new BasicShopifyAPI;
    $api->setShop('example.myshopify.com');

    $this->assertEquals('example.myshopify.com', $api->getShop());
  }

 /**
  * @test
  *
  * Should set access token
  */
  function itShouldSetAccessToken() {
    $api = new BasicShopifyAPI;
    $api->setAccessToken('123');

    $reflected = new ReflectionClass($api);

    $access_token_property = $reflected->getProperty('access_token');
    $access_token_property->setAccessible(true);

    $this->assertEquals('123', $access_token_property->getValue($api));
  }

  /**
   * @test
   *
   * Should set API key and API password and API shared secret
   */
  function itShouldSetApiKeyAndPassword() {
    $api = new BasicShopifyAPI;
    $api->setApiKey('123');
    $api->setApiPassword('abc');
    $api->setApiSecret('!@#');

    $reflected = new ReflectionClass($api);

    $api_key_property = $reflected->getProperty('api_key');
    $api_key_property->setAccessible(true);

    $api_password_property = $reflected->getProperty('api_password');
    $api_password_property->setAccessible(true);

    $api_secret_property = $reflected->getProperty('api_secret');
    $api_secret_property->setAccessible(true);

    $this->assertEquals('123', $api_key_property->getValue($api));
    $this->assertEquals('abc', $api_password_property->getValue($api));
    $this->assertEquals('!@#', $api_secret_property->getValue($api));
  }

  /**
   * @test
   *
   * Checking base URL for API calls on public
   */
  function itShouldReturnBaseUrl() {
    $api = new BasicShopifyAPI;
    $api->setShop('example.myshopify.com');

    $reflected = new ReflectionClass($api);

    $base_url_method = $reflected->getMethod('getBaseUrl');
    $base_url_method->setAccessible(true);

    $this->assertEquals('https://example.myshopify.com', $base_url_method->invoke($api));
  }

  /**
   * @test
   *
   * Checking base URL for API calls on private
   */
  function itShouldReturnPrivateBaseUrl() {
    $api = new BasicShopifyAPI(true);
    $api->setShop('example.myshopify.com');
    $api->setApiKey('123');
    $api->setApiPassword('abc');

    $reflected = new ReflectionClass($api);

    $base_url_method = $reflected->getMethod('getBaseUrl');
    $base_url_method->setAccessible(true);

    $this->assertEquals('https://123:abc@example.myshopify.com', $base_url_method->invoke($api));
  }

  /**
   * @test
   *
   * Should get auth URL containing shop and API key
   */
  function itShouldGetgetInstallUrl() {
    $api = new BasicShopifyAPI;
    $api->setShop('example.myshopify.com');
    $api->setApiKey('123');

    $this->assertEquals('https://example.myshopify.com/admin/api/auth?api_key=123', $api->getInstallUrl());
  }

  /**
   * @test
   * @expectedException Exception
   * @expectedExceptionMessage Shopify domain missing for API calls
   *
   * Ensure Shopify domain is passed to API
   */
  function itShouldThrowExceptionForMissingShopifyDomain() {
    $api = new BasicShopifyAPI;
    $api->getInstallUrl();
  }

  /**
   * @test
   * @expectedException Exception
   * @expectedExceptionMessage API key and password required for private Shopify API calls
   *
   * Ensure Shopify API details is passsed for private API calls
   */
  function itShouldThrowExceptionForMissingApiDetails() {
    $api = new BasicShopifyAPI(true);
    $api->getInstallUrl();
  }

  /**
   * @test
   *
   * Should allow for own client injection
   */
  function itShouldAllowForOwnClient() {
    $api = new BasicShopifyAPI;
    $api->setClient(new Client(['handler' => new MockHandler]));

    $reflected = new ReflectionClass($api);

    $client_property = $reflected->getProperty('client');
    $client_property->setAccessible(true);

    $value = $client_property->getValue($api);

    $this->assertEquals('GuzzleHttp\Handler\MockHandler', get_class($value->getConfig('handler')));
  }

  /**
   * @test
   * @expectedException Exception
   * @expectedExceptionMessage API secret is missing
   *
   * Ensure Shopify API secret is there for grabbing the access tokens
   */
  function itShouldThrowExceptionForMissingApiSecret() {
    $api = new BasicShopifyAPI(true);
    $api->getAccessToken('123');
  }

  /**
   * @test
   *
   * Should get access token from Shopify
   */
  function itShouldGetAccessTokenFromShopify() {
    $response = new Response(
      200,
      [],
      file_get_contents(__DIR__.'/fixtures/admin__oauth__access_token.json')
    );
    $mock    = new MockHandler([$response]);
    $client  = new Client(['handler' => $mock]);

    $api = new BasicShopifyAPI;
    $api->setShop('example.myshopify.com');
    $api->setApiSecret('abc');
    $api->setClient($client);

    $this->assertEquals('f85632530bf277ec9ac6f649fc327f17', $api->getAccessToken('123'));
  }

  /**
   * @test
   *
   * Should get auth URL
   */
  function itShouldReturnAuthUrl() {
    $api = new BasicShopifyAPI;
    $api->setShop('example.myshopify.com');
    $api->setApiKey('123');

    $this->assertEquals(
      'https://example.myshopify.com/admin/oauth/authorize?client_id=123&scopes=read_products,write_products&redirect_uri=https://localapp.local/',
      $api->getAuthUrl(['read_products', 'write_products'], 'https://localapp.local/')
    );
  }

  /**
   * @test
   *
   * Check verify with no params
   */
  function itShouldFailRequestVerifyWithNoParams() {
    $api = new BasicShopifyAPI;
    $this->assertEquals(false, $api->verifyRequest(null));
  }

  /**
   * @test
   *
   * Check verify with params
   */
  function itShouldPassRequestVerifyWithParams() {
    $hmac   = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
    $params = [
      'code'      => '0907a61c0c8d55e99db179b68161bc00',
      'hmac'      => $hmac,
      'shop'      => 'some-shop.myshopify.com',
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
  function itShouldPassRequestVerifyWithBadParams() {
    $hmac   = '4712bf92ffc2917d15a2f5a273e39f0116667419aa4b6ac0b3baaf26fa3c4d20';
    $params = [
      'code'      => '0907a61c0c8d55e99db179b68161bc00-OOPS',
      'hmac'      => $hmac,
      'shop'      => 'some-shop.myshopify.com'
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
  function itShouldReturnGuzzleResponseAndJsonBody() {
    $response = new Response(
      200,
      ['http_x_shopify_shop_api_call_limit' => '2/80'],
      file_get_contents(__DIR__.'/fixtures/admin__shop.json')
    );

    $mock     = new MockHandler([$response]);
    $client   = new Client(['handler' => $mock]);

    $api = new BasicShopifyAPI;
    $api->setClient($client);
    $api->setShop('example.myshopify.com');
    $api->setApiKey('123');
    $api->setAccessToken('!@#');

    $request = $api->request('GET', '/admin/shop.json');

    $this->assertEquals(true, is_object($request));
    $this->assertInstanceOf('GuzzleHttp\Psr7\Response', $request->response);
    $this->assertEquals(200, $request->response->getStatusCode());
    $this->assertEquals(true, is_object($request->body));
    $this->assertEquals('Apple Computers', $request->body->shop->name);
  }

  /**
   * @test
   * @expectedException Exception
   * @expectedExceptionMessage Invalid API call limit key. Valid keys are: left, made, limit
   *
   * Ensure we pass a valid key to the API calls
   */
  function itShouldThrowExceptionForInvalidApiCallsKey() {
    $api = new BasicShopifyAPI;
    $api->getApiCalls('oops');
  }

  /**
   * @test
   *
   * Should get API call limits
   */
  function itShouldReturnApiCallLimits() {
    $response = new Response(200, ['http_x_shopify_shop_api_call_limit' => '2/80'], '{}');
    $mock     = new MockHandler([$response]);
    $client   = new Client(['handler' => $mock]);

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
}
