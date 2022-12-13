<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Util;

/**
 * Responsibility for protection against clickjaking
 */
class IframeProtection
{
    /**
     * The shop querier.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Constructor.
     *
     * @param IShopQuery  $shopQuery The shop querier.
     *
     * @return void
     */
    public function __construct(
        IShopQuery $shopQuery
    ) {
        $this->shopQuery = $shopQuery;
    }

    /**
     * Set frame-ancestors header
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $ancestors = Util::getShopifyConfig('iframe_ancestors');

        $shop = Cache::remember(
            'frame-ancestors_'.$request->get('shop'),
            now()->addMinutes(20),
            function () use ($request) {
                return $this->shopQuery->getByDomain(ShopDomain::fromRequest($request));
            }
        );

        $domain = $shop
            ? $shop->name
            : '*.myshopify.com';

        $iframeAncestors = "frame-ancestors https://admin.shopify.com https://$domain";

        if (!blank($ancestors)) {
            $iframeAncestors .= ' ' . $ancestors;
        }

        $response->headers->set(
            'Content-Security-Policy',
            $iframeAncestors
        );

        return $response;
    }
}
