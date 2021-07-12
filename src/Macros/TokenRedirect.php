<?php

namespace Osiset\ShopifyApp\Macros;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

/**
 * Method for passing a request through the token route.
 * Used for non-SPAs.
 */
class TokenRedirect extends TokenUrl
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
        [$url, $params] = $this->generateParams($route, $params, $absolute);

        return Redirect::route($url, $params);
    }
}
