<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Exception;
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

        // Get the shop domain
        $shopDomain = $this->getShopDomain($request, $session);

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
     * Grab the shop's myshopify domain from query, referer or session.
     *
     * Getting the domain for the shop from session is unreliable
     * because if 2 shops have the same app open in the same browser
     * (e.g. someone is managing the same app on 2 stores at the same
     * time) then the sessions can bleed into each other due to the
     * cookies being run on the same domain (the domain of the app,
     * not the individual shops admin dashboard).
     *
     * To get around this select a domain based on other information
     * available, and make sure to verify the input before use. This is
     * still not 100% reliable.
     *
     * Order of precedence is:
     *
     *  - GET variable
     *  - Referer
     *  - Session
     *
     * @param \Illuminate\Http\Request                  $request
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session
     *
     * @throws Exception
     *
     * @return bool|string
     */
    private function getShopDomain(Request $request, ShopSession $session)
    {
        // Query variable is highest priority
        $shopDomainParam = $this->getQueryDomain($request);
        if ($shopDomainParam) {
            return ShopifyApp::sanitizeShopDomain($shopDomainParam);
        }

        // Then the value in the referer header (if validated)
        // See issue https://github.com/ohmybrew/laravel-shopify/issues/295
        $shopRefererParam = $this->getRefererDomain($request);
        if ($shopRefererParam) {
            return ShopifyApp::sanitizeShopDomain($shopRefererParam);
        }

        // Grab the shop's myshopify domain from query or session
        // For SPA's we need X-Shop-Domain
        // See issue https://github.com/ohmybrew/laravel-shopify/issues/295
        $shopHeaderParam = $this->getHeaderDomain($request);
        if ($shopHeaderParam) {
            return ShopifyApp::sanitizeShopDomain($shopHeaderParam);
        }

        // If neither are available then pull from the session
        $shopDomainSession = $session->getDomain();
        if ($shopDomainSession) {
            return ShopifyApp::sanitizeShopDomain($shopDomainSession);
        }

        // No domain :(
        throw new Exception('Unable to get shop domain.');
    }

    /**
     * Get the query variable shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws Exception
     *
     * @return bool|string
     */
    private function getQueryDomain(Request $request)
    {
        // Extract the referer
        $shop = $request->input('shop');
        if (!$shop) {
            return false;
        }

        $signature = $request->input('hmac');
        $timestamp = $request->input('timestamp');
        $code = $request->input('code');

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest([
            'shop' => $shop,
            'hmac' => $signature,
            'timestamp' => $timestamp,
            'code' => $code,
        ])) {
            return $shop;
        }

        throw new Exception('Unable to verify signature.');
    }

    /**
     * Get the referer shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return bool|string
     */
    private function getRefererDomain(Request $request)
    {
        // Extract the referer
        $referer = $request->header('referer');
        if (!$referer) {
            return false;
        }

        // Get the values of the referer query params as an array
        $url = parse_url($referer, PHP_URL_QUERY);
        parse_str($url, $refererQueryParams);
        if (!$refererQueryParams) {
            return false;
        }

        if (!isset($refererQueryParams['shop']) || !isset($refererQueryParams['hmac']) || !isset($refererQueryParams['timestamp']) || !isset($refererQueryParams['code'])) {
            return false;
        }

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest($refererQueryParams)) {
            return $refererQueryParams['shop'];
        }

        throw new Exception('Unable to verify signature.');
    }

    /**
     * Get the header shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws Exception
     *
     * @return bool|string
     */
    private function getHeaderDomain(Request $request)
    {
        // Extract the referer
        $shop = $request->header('X-Shop-Domain');
        if (!$shop) {
            return false;
        }

        $signature = $request->header('X-Shop-Signature');
        $timestamp = $request->header('X-Shop-Time');
        $code = $request->header('X-Shop-Code');

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest([
            'shop' => $shop,
            'hmac' => $signature,
            'timestamp' => $timestamp,
            'code' => $code,
        ])) {
            return $shop;
        }

        throw new Exception('Unable to verify signature.');
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
