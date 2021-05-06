<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use function Osiset\ShopifyApp\getShopifyConfig;

/**
 * Responsible for ensuring the shop is being billed.
 */
class Billable
{
    /**
     * Checks if a shop has paid for access.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (getShopifyConfig('billing_enabled') === true) {
            /** @var $shop IShopModel */
            $shop = auth()->user();
            if (! $shop->isFreemium() && ! $shop->isGrandfathered() && ! $shop->plan) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                return Redirect::route(getShopifyConfig('route_names.billing'), $request->input());
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
