<?php

namespace Osiset\ShopifyApp\Test\Macros;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;

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
            ],
        );
        Request::swap($newRequest);

        // Run the macro and get the location header
        $response = Redirect::tokenRedirect('home');
        $location = $response->headers->get('location');

        $this->assertEquals(
            'http://localhost/authenticate/token?shop=example.myshopify.com&target=http%3A%2F%2Flocalhost',
            $location
        );
    }
}
