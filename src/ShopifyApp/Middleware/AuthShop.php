<?php

namespace OhMyBrew\ShopifyApp\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Services\AuthShopHandler;
use OhMyBrew\ShopifyApp\Exceptions\MissingShopDomainException;
use OhMyBrew\ShopifyApp\Exceptions\SignatureVerificationException;

/**
 * Response for ensuring an authenticated shop.
 */
class AuthShop
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request The request object.
     * @param \Closure                 $next    The "next" action to take.
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
     * @param \Illuminate\Http\Request $request The request object.
     *
     * @return bool|\Illuminate\Http\RedirectResponse
     */
    protected function validateShop(Request $request)
    {
        // Setup the session service
        $session = new ShopSession();

        // Grab the shop's myshopify domain from query or session
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
     * Grab the shop's myshopify domain from query, referer, headers
     * or session.
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
     *  - GET/POST Variable
     *  - Referer
     *  - Headers
     *  - Session
     *
     * @param \Illuminate\Http\Request                  $request The request object.
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session The shop session instance.
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

        // Grab the shop's myshopify domain from headers
        // Referer is more reliable
        // For SPA's we need X-Shop-Domain and verification headers
        // See issue https://github.com/ohmybrew/laravel-shopify/issues/295
        $shopHeaderParam = $this->getHeaderDomain($request);
        if ($shopHeaderParam) {
            return ShopifyApp::sanitizeShopDomain($shopHeaderParam);
        }

        // If none of the above are available then pull from the session
        $shopDomainSession = $session->getDomain();
        if ($shopDomainSession) {
            return ShopifyApp::sanitizeShopDomain($shopDomainSession);
        }

        // No domain :(
        throw new MissingShopDomainException('Unable to get shop domain.');
    }

    /**
     * Get the query variable shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request The request object.
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

        // Always
        $signature = $request->input('hmac');
        $timestamp = $request->input('timestamp');

        $verify = [
            'shop'      => $shop,
            'hmac'      => $signature,
            'timestamp' => $timestamp,
        ];

        // Sometimes
        $code = $request->input('code') ?? null;
        $locale = $request->input('locale') ?? null;
        $state = $request->input('state') ?? null;
        $id = $request->input('id') ?? null;

        if ($code) {
            $verify['code'] = $code;
        }

        if ($locale) {
            $verify['locale'] = $locale;
        }

        if ($state) {
            $verify['state'] = $state;
        }

        if ($id) {
            $verify['id'] = $id;
        }

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest($verify)) {
            return $shop;
        }

        throw new SignatureVerificationException('Unable to verify signature.');
    }

    /**
     * Get the referer shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request The request object.
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

        // These 3 must always be present
        if (!isset($refererQueryParams['shop']) || !isset($refererQueryParams['hmac']) || !isset($refererQueryParams['timestamp'])) {
            return false;
        }

        $verify = [
            'shop'      => $refererQueryParams['shop'],
            'hmac'      => $refererQueryParams['hmac'],
            'timestamp' => $refererQueryParams['timestamp'],
        ];

        // Sometimes present
        $code = $refererQueryParams['code'] ?? null;
        $locale = $refererQueryParams['locale'] ?? null;
        $state = $refererQueryParams['state'] ?? null;
        $id = $refererQueryParams['id'] ?? null;

        if ($code) {
            $verify['code'] = $code;
        }

        if ($locale) {
            $verify['locale'] = $locale;
        }

        if ($state) {
            $verify['state'] = $state;
        }

        if ($id) {
            $verify['id'] = $id;
        }

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest($verify)) {
            return $refererQueryParams['shop'];
        }

        throw new SignatureVerificationException('Unable to verify signature.');
    }

    /**
     * Get the header shopify domain from the request and validate.
     *
     * It is dangerous to blindly trust user input so we need to
     * check and confirm the validity upfront before we return the
     * value to anything.
     *
     * @param \Illuminate\Http\Request $request The request object.
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

        // Always present
        $signature = $request->header('X-Shop-Signature');
        $timestamp = $request->header('X-Shop-Time');

        $verify = [
            'shop'      => $shop,
            'hmac'      => $signature,
            'timestamp' => $timestamp,
        ];

        // Sometimes present
        $code = $request->header('X-Shop-Code') ?? null;
        $locale = $request->header('X-Shop-Locale') ?? null;
        $state = $request->header('X-Shop-State') ?? null;
        $id = $request->header('X-Shop-ID') ?? null;

        if ($code) {
            $verify['code'] = $code;
        }

        if ($locale) {
            $verify['locale'] = $locale;
        }

        if ($state) {
            $verify['state'] = $state;
        }

        if ($id) {
            $verify['id'] = $id;
        }

        // Make sure there is no param spoofing attempt
        if (ShopifyApp::api()->verifyRequest($verify)) {
            return $shop;
        }

        throw new SignatureVerificationException('Unable to verify signature.');
    }

    /**
     * Gets the appropriate flow type. It either returns full, partial,
     * or false. If it returns false it means that everything is fine.
     *
     * @param object                                    $shop    The shop model.
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

        // disable partial auth
        return AuthShopHandler::FLOW_FULL;

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
     * @param \OhMyBrew\ShopifyApp\Services\ShopSession $session    The shop session instance.
     * @param \Illuminate\Http\Request                  $request    The request object.
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
