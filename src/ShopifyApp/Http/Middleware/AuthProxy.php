<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use function Osiset\ShopifyApp\createHmac;
use function Osiset\ShopifyApp\getShopifyConfig;
use Osiset\ShopifyApp\Objects\Values\NullableShopDomain;
use function Osiset\ShopifyApp\parseQueryString;
use Osiset\ShopifyApp\Services\ShopSession;

/**
 * Responsible for ensuring a proper app proxy request.
 */
class AuthProxy
{
    /**
     * Shop session helper.
     *
     * @var ShopSession
     */
    protected $shopSession;

    /**
     * Constructor.
     *
     * @param ShopSession $shopSession Shop session helper.
     *
     * @return void
     */
    public function __construct(ShopSession $shopSession)
    {
        $this->shopSession = $shopSession;
    }

    /**
     * Handle an incoming request to ensure it is valid.
     *
     * @param Request  $request The request object.
     * @param \Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the query parameters we need
        $query = $this->getQueryStringParameters($request);
        $signature = $query['signature'] ?? null;
        $shop = NullableShopDomain::fromNative($query['shop'] ?? null);

        if (isset($query['signature'])) {
            // Remove signature since its not part of the signature calculation
            unset($query['signature']);
        }

        // Build a local signature
        $signatureLocal = createHmac(
            [
                'data'       => $query,
                'buildQuery' => true,
            ],
            getShopifyConfig('api_secret', $shop)
        );
        if (hash_equals($signature, $signatureLocal) === false || $shop->isNull()) {
            // Issue with HMAC or missing shop header
            return Response::make('Invalid proxy signature.', 401);
        }

        // Login the shop
        $this->shopSession->make($shop);

        // All good, process proxy request
        return $next($request);
    }

    /**
     * Parse query strings the same way Shopify does.
     *
     * @param Request  $request The request object.
     *
     * @return array
     */
    protected function getQueryStringParameters(Request $request): array
    {
        return parseQueryString($request->server->get('QUERY_STRING'));
    }
}
