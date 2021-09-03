<?php

namespace Osiset\ShopifyApp\Test\Macros;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Test\TestCase;

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
            ]
        );
        Request::swap($newRequest);

        // Run the macro and get the link
        $result = UrlGenerator::tokenRoute('home');

        $this->assertSame(
            'http://localhost/authenticate/token?shop=example.myshopify.com&target=http%3A%2F%2Flocalhost',
            $result
        );
    }
}
