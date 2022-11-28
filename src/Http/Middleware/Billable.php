<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Osiset\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for ensuring the shop is being billed.
 */
class Billable
{
    /**
     * The shop querier.
     *
     * @var ShopQuery
     */
    protected $shopQuery;

    /**
     * @param ShopQuery $shopQuery The shop querier.
     *
     * @return void
     */
    public function __construct(ShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
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
            /** @var $shop IShopModel */
            $shop = $this->shopQuery->getByDomain(ShopDomain::fromNative($request->get('shop')));
            if (! $shop->plan && ! $shop->isFreemium() && ! $shop->isGrandfathered()) {
                // They're not grandfathered in, and there is no charge or charge was declined... redirect to billing
                return Redirect::route(
                    Util::getShopifyConfig('route_names.billing'),
                    array_merge($request->input(), ['shop' => $shop->getDomain()->toNative()])
                );
            }
        }

        // Move on, everything's fine
        return $next($request);
    }
}
