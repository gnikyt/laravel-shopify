<?php

namespace Osiset\ShopifyApp\Test\Macros;

use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Test\TestCase;

class TokenRedirectTest extends TestCase
{
    public function testTokenRedirect(): void
    {
        // Setup request
        $currentRequest = Request::instance();
        $newRequest = $currentRequest->duplicate(
            // Query Params
            [
                'shop' => 'example.myshopify.com',
            ]
        );
        Request::swap($newRequest);

        // Run the macro and get the location header
        $response = Redirector::tokenRedirect('home');
        $location = $response->headers->get('location');

        $this->assertSame(
            'http://localhost/authenticate/token?shop=example.myshopify.com&target=http%3A%2F%2Flocalhost',
            $location
        );
    }
}
