<?php

namespace Osiset\ShopifyApp\Macros;

use Osiset\ShopifyApp\Util;
use Illuminate\Support\Facades\URL;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Method for passing a request through the token route.
 * Used for non-SPAs.
 */
class TokenRedirect
{
    /**
     * Return a URL to token path with shop and target (for redirect).
     *
     * @param string $route    The route name.
     * @param array  $params   Additional route params.
     * @param bool   $absolute Absolute or relative?
     *
     * @example `return Redirect::tokenRedirect('orders.view', ['id' => 1]);`
     *
     * @return RedirectResponse
     */
    public function __invoke(string $route, $params = [], bool $absolute = true): RedirectResponse
    {
        return Redirect::route(
            Util::getShopifyConfig('route_names.authenticate.token'),
            [
                'shop'   => ShopDomain::fromRequest(Request::instance()),
                'target' => URL::route($route, $params, $absolute),
            ]
        );
    }
}
