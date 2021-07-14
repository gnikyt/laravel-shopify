<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Http\Middleware\AuthProxy as AuthProxyMiddleware;
use Osiset\ShopifyApp\Test\TestCase;

class AuthProxyTest extends TestCase
{
    protected $queryString;
    protected $queryParams;
    protected $queryStringArrayFormat;
    protected $queryParamsArrayFormat;

    public function setUp(): void
    {
        parent::setUp();

        // Make the shop
        factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        // From Shopify docs
        $this->queryString = 'extra=1&extra=2&shop=shop-name.myshopify.com&path_prefix=%2Fapps%2Fawesome_reviews&timestamp=1317327555&signature=a9718877bea71c2484f91608a7eaea1532bdf71f5c56825065fa4ccabe549ef3';

        // From Shopify docs
        $this->queryParams = [
            'extra' => ['1', '2'],
            'shop' => 'shop-name.myshopify.com',
            'path_prefix' => '/apps/awesome_reviews',
            'timestamp' => '1317327555',
            'signature' => 'a9718877bea71c2484f91608a7eaea1532bdf71f5c56825065fa4ccabe549ef3',
        ];

        // Array parameter format
        $this->queryStringArrayFormat = 'extra[]=1&extra[]=2&shop=shop-name.myshopify.com&path_prefix=%2Fapps%2Fawesome_reviews&timestamp=1317327555&signature=6f4b878d5340128aab03a234676dba228432b0b8b72863828ec143e4c5772124';

        $this->queryParamsArrayFormat = [
            'extra' => ['1', '2'],
            'shop' => 'shop-name.myshopify.com',
            'path_prefix' => '/apps/awesome_reviews',
            'timestamp' => '1317327555',
            'signature' => '6f4b878d5340128aab03a234676dba228432b0b8b72863828ec143e4c5772124',
        ];

        // Set the app secret to match Shopify docs
        $this->app['config']->set('shopify-app.api_secret', 'hush');
    }

    public function testRuns(): void
    {
        Request::merge($this->queryParams);
        Request::instance()->server->set('QUERY_STRING', $this->queryString);

        // Run the middleware
        $result = $this->runMiddleware(AuthProxyMiddleware::class);

        // Confirm full run
        $this->assertTrue($result[0]);
    }

    public function testDeniesForMissingShop(): void
    {
        // Remove shop from params
        $query = $this->queryParams;
        unset($query['shop']);
        Request::merge($query);
        Request::instance()->server->set('QUERY_STRING', 'extra=1&extra=2&path_prefix=%2Fapps%2Fawesome_reviews&timestamp=1317327555&signature=a9718877bea71c2484f91608a7eaea1532bdf71f5c56825065fa4ccabe549ef3');

        // Run the middleware
        $result = $this->runMiddleware(AuthProxyMiddleware::class);

        // Assert it was not processed and our status
        $this->assertFalse($result[0]);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $result[1]->status());
    }

    public function testDoesNotRunForInvalidSignature(): void
    {
        // Make the signature invalid
        $query = $this->queryParams;
        $query['oops'] = 'i-did-it-again';
        Request::merge($query);
        Request::instance()->server->set('QUERY_STRING', $this->queryString.'&oops=i-did-it-again');

        // Run the middleware
        $result = $this->runMiddleware(AuthProxyMiddleware::class);

        // Assert it was not processed and our status
        $this->assertFalse($result[0]);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $result[1]->status());
    }

    public function testQueryStringArrayFormatParsedProperly(): void
    {
        Request::merge($this->queryParamsArrayFormat);
        Request::instance()->server->set('QUERY_STRING', $this->queryStringArrayFormat);

        // Run the middleware using Rack-based query string parsing
        $result = $this->runMiddleware(AuthProxyMiddleware::class);

        $this->assertTrue($result[0]);
    }
}
