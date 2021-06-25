<?php

namespace Osiset\ShopifyApp\Macros;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Util;

/**
 * Common URL generation for TokenRedirect and TokenRoute macros.
 */
abstract class TokenUrl
{
    /**
     * Return a URL to token path with shop and target.
     *
     * @param string $route    The route name.
     * @param array  $params   Additional route params.
     * @param bool   $absolute Absolute or relative?
     *
     * @return array
     */
    public function generateParams(string $route, $params = [], bool $absolute = true): array
    {
        return [
            Util::getShopifyConfig('route_names.authenticate.token'),
            [
                'shop'   => ShopDomain::fromRequest(Request::instance())->toNative(),
                'target' => URL::route($route, $params, $absolute),
            ],
        ];
    }
}
