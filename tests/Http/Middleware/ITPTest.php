<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;
use Osiset\ShopifyApp\Http\Middleware\ITP as ITPMiddleware;
use Osiset\ShopifyApp\Test\TestCase;

class ITPTest extends TestCase
{
    public function testShouldFullPageRedirectIfNoItpCookie(): void
    {
        // Missing ITP cookie
        $result = $this->runItp(
            null,
            ['shop' => 'example.myshopify.com']
        );

        $this->assertInstanceOf(Response::class, $result[0]);
        $this->assertFalse($result[1]);
    }

    public function testShouldRedirectToAsk(): void
    {
        // Missing ITP cookie and ITP was attempted
        $result = $this->runItp(
            null,
            [
                'shop' => 'example.myshopify.com',
                'itp' => true,
            ]
        );

        $this->assertInstanceOf(RedirectResponse::class, $result[0]);
        $this->assertFalse($result[1]);
    }

    public function testShouldRun(): void
    {
        // Has ITP cookie
        $result = $this->runItp(
            null,
            ['shop' => 'example.myshopify.com'],
            ['itp' => true]
        );

        $this->assertTrue($result[1]);
    }

    /**
     * @param callable|null $cb
     * @param array $query
     * @return array
     */
    private function runItp($cb = null, array $query = [], array $cookies = []): array
    {
        $request = Request::instance()->duplicate($query, null, null, $cookies, null, null);

        $called = false;
        $response = ($this->app->make(ITPMiddleware::class))->handle($request, function ($request) use (&$called, $cb) {
            $called = true;

            if ($cb) {
                $cb($request);
            }
        });

        return [$response, $called];
    }
}
