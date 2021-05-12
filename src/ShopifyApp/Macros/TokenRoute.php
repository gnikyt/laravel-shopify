<?php

namespace Osiset\ShopifyApp\Macros;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

use function Osiset\ShopifyApp\getShopifyConfig;

/**
 * Method for generating a URL to the token route.
 * Used for non-SPAs.
 */
class TokenRoute
{
    /**
     * Return a URL to token path with shop and target (for redirect).
     *
     * @param string $route    The route name.
     * @param array  $params   Additional route params.
     * @param bool   $absolute Absolute or relative?
     *
     * @example `URL::tokenRoute('orders.view', ['id' => 1]);`
     * @example `<a href="{{ URL::tokenRoute('orders.view', ['id' => 1]) }}">Order #1</a>`
     *
     * @return string
     */
    public function __invoke(string $route, $params = [], bool $absolute = true): string
    {
        return URL::route(
            getShopifyConfig('route_names.authenticate.token'),
            [
                'shop'   => ShopDomain::fromRequest(Request::instance()),
                'target' => URL::route($route, $params, $absolute),
            ]
        );
    }
}
