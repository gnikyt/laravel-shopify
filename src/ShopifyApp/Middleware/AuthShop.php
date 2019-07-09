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

        return $this->response($request, $next);
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
            // Shop loaded?
            $shop === null ||

            // Shop is trashed?
            $shop->trashed() ||

            // Shop loaded does not match incoming shop?
            ($shopDomain && $shopDomain !== $shop->shopify_domain) === true ||

            // Session valid?
            !$session->isValid()
        ) {
            // We need to handle this issue...
            return $this->handleBadSession($session, $request, $shopDomain);
        }

        return true;
    }

    /**
     * Come back with a response.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    protected function response(Request $request, Closure $next)
    {
        // Shop is OK, now check if Appbridge is enabled and this is not a JSON/AJAX request...
        $response = $next($request);
        if (
            Config::get('shopify-app.appbridge_enabled') &&
            ($request->ajax() || $request->expectsJson() || $request->isJson()) === false
        ) {
            if (($response instanceof BaseResponse) === false) {
                // Not an instance of a Symfony response, override
                $response = new Response($response);
            }

            // Attempt to modify headers applicable to AppBridge (does not work in all cases)
            $response->headers->set('P3P', 'CP="Not used"');
            $response->headers->remove('X-Frame-Options');
        }

        return $response;
    }

    /**
     * Handles a bad shop session.
     *
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session    The session service for the shop.
     * @param \Illuminate\Http\Request                  $request    The incoming request.
     * @param string|null                               $shopDomain The incoming shop domain.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function handleBadSession(
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
                'type' => $session->isType(ShopSession::GRANT_PERUSER) ?
                    AuthShopHandler::FLOW_PARTIAL :
                    AuthShopHandler::FLOW_PARTIAL,
                'shop' => $shopDomain,
            ]
        );
    }
}
