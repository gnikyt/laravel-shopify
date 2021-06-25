<?php

namespace Osiset\ShopifyApp\Test\Macros;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;

class TokenRouteTest extends TestCase
{
    public function testTokenRoute(): void
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

        // Run the macro and get the link
        $result = URL::tokenRoute('home');

        $this->assertEquals(
            'http://localhost/authenticate/token?shop=example.myshopify.com&target=http%3A%2F%2Flocalhost',
            $result
        );
    }
}
