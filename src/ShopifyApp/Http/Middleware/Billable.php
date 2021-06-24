<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for ensuring the shop is being billed.
 */
class Billable
{
    /**
     * The shop session helper.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Setup.
     *
     * @param ShopSession $shopSession The shop session helper.
     *
     * @return void
     */
    public function __construct(ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;
    }

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
        if (Util::getShopifyConfig('billing_enabled') === true) {
            $shop = $this->shopSession->getShop();
            if (! $shop->isFreemium() && ! $shop->isGrandfathered() && ! $shop->plan) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                return Redirect::route(Util::getShopifyConfig('route_names.billing'), $request->input());
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
