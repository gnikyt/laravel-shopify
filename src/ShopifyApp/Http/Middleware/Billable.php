<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Responsible for ensuring the shop is being billed.
 */
class Billable
{
    use ConfigAccessible;

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
        if ($this->getConfig('billing_enabled') === true) {
            $shop = Auth::user();
            if (!$shop->isFreemium() && !$shop->isGrandfathered() && !$shop->plan) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                return Redirect::route('billing');
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
