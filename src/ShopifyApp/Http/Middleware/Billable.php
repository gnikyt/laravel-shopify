<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Responsible for ensuring the shop is being billed.
 */
class Billable
{
    use ConfigAccessible;

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
        Log::info('constructing billable...');
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
        if ($this->getConfig('billing_enabled') === true) {
            Log::info('billing enabled!');
            $shop = $this->shopSession->getShop();
            Log::info('shop:');
            // dd($shop);
            if (! $shop->isFreemium() && ! $shop->isGrandfathered() && ! $shop->plan) {
                // dd($shop, $request->input(), '<<');
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                return Redirect::route('billing', $request->input());
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
