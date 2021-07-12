<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Osiset\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Objects\Values\Hmac;
use Osiset\ShopifyApp\Objects\Values\NullableShopDomain;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for ensuring a proper app proxy request.
 */
class AuthProxy
{
    /**
     * The auth manager.
     *
     * @var AuthManager
     */
    protected $auth;

    /**
     * The shop querier.
     *
     * @var ShopQuery
     */
    protected $shopQuery;

    /**
     * Constructor.
     *
     * @param AuthManager $auth      The Laravel auth manager.
     * @param ShopQuery   $shopQuery The shop querier.
     *
     * @return void
     */
    public function __construct(AuthManager $auth, ShopQuery $shopQuery)
    {
        $this->auth = $auth;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Handle an incoming request to ensure it is valid.
     *
     * @param Request $request The request object.
     * @param Closure $next    The next action.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Grab the query parameters we need
        $query = Util::parseQueryString($request->server->get('QUERY_STRING'));
        $signature = Arr::get($query, 'signature', '');
        $shop = NullableShopDomain::fromNative(Arr::get($query, 'shop'));

        if (! empty($signature)) {
            // Remove signature since its not part of the signature calculation
            Arr::forget($query, 'signature');
        }

        // Build a local signature
        $signatureLocal = Util::createHmac(
            [
                'data'       => $query,
                'buildQuery' => true,
            ],
            Util::getShopifyConfig('api_secret', $shop)
        );
        if ($shop->isNull() || ! Hmac::fromNative($signature)->isSame($signatureLocal)) {
            // Issue with HMAC or missing shop header
            return Response::make('Invalid proxy signature.', HttpResponse::HTTP_UNAUTHORIZED);
        }

        // Login the shop
        $shop = $this->shopQuery->getByDomain($shop);
        if ($shop) {
            $this->auth->login($shop);
        }

        // All good, process proxy request
        return $next($request);
    }
}
