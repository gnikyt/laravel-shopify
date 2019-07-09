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
use Symfony\Component\HttpFoundation\Response as BaseResponse;

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
        $shopDomain = ShopifyApp::sanitizeShopDomain(
            $request->filled('shop') ? $request->get('shop') : $session->getDomain()
        );

        // Get the shop based on domain and update the session service
        $shop = ShopifyApp::shop($shopDomain);
        $session->setShop($shop);

        if (
            $shop === null ||
            $shop->trashed() ||
            ($shopDomain && $shopDomain !== $shop->shopify_domain) === true
        ) {
            // We need to handle this issue...
            return $this->handleBadSession(
                AuthShopHandler::FLOW_FULL,
                $session,
                $request,
                $shopDomain
            );
        } else if (!$session->isValid()) {
            // We need to handle this issue...
            return $this->handleBadSession(
                $session->isType(ShopSession::GRANT_PERUSER) ? AuthShopHandler::FLOW_FULL : AuthShopHandler::FLOW_PARTIAL,
                $session,
                $request,
                $shopDomain
            );
        } else {
            // Everything is fine!
            return true;
        }
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
            [
                'type' => $type,
                'shop' => $shopDomain,
            ]
        );
    }
}
