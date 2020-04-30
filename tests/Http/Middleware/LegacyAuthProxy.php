<?php

namespace Osiset\ShopifyApp\Test\Http\Middleware;

use Illuminate\Http\Request;
use Osiset\ShopifyApp\Http\Middleware\AuthProxy;

/**
 * Uses Laravel query string parsing to show invalid signatures for array parameters.
 */
class LegacyAuthProxy extends AuthProxy
{
    /**
     * Use Laravel query string parsing to show invalid signatures.
     *
     * @param Request  $request The request object.
     *
     * @return array
     */
    protected function getQueryStringParameters(Request $request): array
    {
        return $request->query->all();
    }
}
