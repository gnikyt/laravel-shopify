<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Services\ShopSession;

/**
 * Response for ensuring an authenticated shop.
 */
class AuthShop
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $validation = $this->validateShop($request);
        if ($validation !== true) {
            return $validation;
        }

        return $next($request);
    }

    /**
     * Checks we have a valid shop.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    protected function validateShop(Request $request)
    {
        // Setup the session service
        $session = new ShopSession();

        // Grab the shop's myshopify domain from query or session
        $shopDomainParam = $request->get('shop');
        $shopDomainSession = $session->getDomain();
        $shopDomain = ShopifyApp::sanitizeShopDomain($shopDomainParam ?? $shopDomainSession);

        // Get the shop based on domain and update the session service
        $shopModel = Config::get('shopify-app.shop_model');
        $shop = $shopModel::withTrashed()
            ->where(['shopify_domain' => $shopDomain])
            ->first();

        $session->setShop($shop);

        $flowType = $this->getFlowType($shop, $session);

        if ($flowType) {
            // We have a bad session
            return $this->handleBadSession(
                $flowType,
                $session,
                $request,
                $shopDomain
            );
        }

        // Everything is fine!
        return true;
    }

    /**
     * Gets the appropriate flow type. It either returns full, partial,
     * or false. If it returns false it means that everything is fine.
     *
     * @param                                           $shop    The shop model.
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session The session service for the shop.
     *
     * @return bool|string
     */
    private function getFlowType($shop, $session)
    {
        // We need to do a full flow if no shop or it is deleted
        if ($shop === null || $shop->trashed()) {
            return AuthShopHandler::FLOW_FULL;
        }

        // Do nothing if the session is valid
        if ($session->isValid()) {
            return false;
        }

        // We need to do a full flow if it grant per user
        if ($session->isType(ShopSession::GRANT_PERUSER)) {
            return AuthShopHandler::FLOW_FULL;
        }

        // Default is the partial flow
        return AuthShopHandler::FLOW_PARTIAL;
    }

    /**
     * Handles a bad shop session.
     *
     * @param string                                    $type       The auth flow to perform.
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session    The session service for the shop.
     * @param \Illuminate\Http\Request                  $request    The incoming request.
     * @param string|null                               $shopDomain The incoming shop domain.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleBadSession(
        string $type,
        ShopSession $session,
        Request $request,
        string $shopDomain = null
    ) {
        // Clear all session variables (domain, token, user, etc)
        $session->forget();

        // Set the return-to path so we can redirect after successful authentication
        Session::put('return_to', $request->fullUrl());

        // Depending on the type of grant mode, we need to do a full auth or partial
        return Redirect::route(
            'authenticate',
            array_merge(
                $request->all(),
                [
                    'type' => $type,
                    'shop' => $shopDomain,
                ]
            )
        );
    }
}
