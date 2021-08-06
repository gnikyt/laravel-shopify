<?php

namespace Osiset\ShopifyApp\Http\Middleware;

use Assert\AssertionFailedException;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel;
use Osiset\ShopifyApp\Exceptions\HttpException;
use Osiset\ShopifyApp\Exceptions\SignatureVerificationException;
use Osiset\ShopifyApp\Objects\Enums\DataSource;
use Osiset\ShopifyApp\Objects\Values\NullableSessionId;
use Osiset\ShopifyApp\Objects\Values\SessionContext;
use Osiset\ShopifyApp\Objects\Values\SessionToken;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Traits\VerifyShopifyMiddleware;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for validating the request.
 */
class VerifyShopifyEmbedded extends VerifyShopify
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
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Previous request shop.
     *
     * @var ShopModel|null
     */
    protected $previousShop;

    /**
     * Constructor.
     *
     * @param AuthManager $auth      The Laravel auth manager.
     * @param IApiHelper  $apiHelper The API helper.
     * @param IShopQuery  $shopQuery The shop querier.
     *
     * @return void
     */
    public function __construct(
        AuthManager $auth,
        IApiHelper $apiHelper,
        IShopQuery $shopQuery
    ) {
        $this->auth = $auth;
        $this->shopQuery = $shopQuery;
        $this->apiHelper = $apiHelper;
        $this->apiHelper->make();
    }

    /**
     * Undocumented function.
     *
     * @param Request $request The request object.
     * @param Closure $next    The next action.
     *
     * @throws SignatureVerificationException If HMAC verification fails.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {

        // Continue if current route is an auth or billing route
        if (Str::contains($request->getRequestUri(), ['/authenticate', '/billing'])) {
            return $next($request);
        }

        $tokenSource = $this->getAccessTokenFromRequest($request);
        if ($tokenSource === null) {
            //Check if there is a store record in the database
            return $this->checkPreviousInstallation($request)
                // Shop exists, token not available, we need to get one
                ? $this->handleMissingToken($request)
                // Shop does not exist
                : $this->handleInvalidShop($request);
        }

        try {
            // Try and process the token
            $token = SessionToken::fromNative($tokenSource);
        } catch (AssertionFailedException $e) {
            // Invalid or expired token, we need a new one
            return $this->handleInvalidToken($request, $e);
        }

        // Set the previous shop (if available)
        if ($request->user()) {
            $this->previousShop = $request->user();
        }

        // Login the shop
        $loginResult = $this->loginShopFromToken(
            $token,
            NullableSessionId::fromNative($request->query('session'))
        );
        if (! $loginResult) {
            // Shop is not installed or something is missing from it's data
            return $this->handleInvalidShop($request);
        }

        return $next($request);
    }

    /**
     * Handle missing token.
     *
     * @param Request $request The request object.
     *
     * @throws HttpException If an AJAX/JSON request.
     *
     * @return mixed
     */
    protected function handleMissingToken(Request $request)
    {
        if ($this->isApiRequest($request)) {
            // AJAX, return HTTP exception
            throw new HttpException(SessionToken::EXCEPTION_INVALID, Response::HTTP_BAD_REQUEST);
        }

        return $this->tokenRedirect($request);
    }

    /**
     * Handle an invalid or expired token.
     *
     * @param Request                  $request The request object.
     * @param AssertionFailedException $e       The assertion failure exception.
     *
     * @throws HttpException If an AJAX/JSON request.
     *
     * @return mixed
     */
    protected function handleInvalidToken(Request $request, AssertionFailedException $e)
    {
        $isExpired = $e->getMessage() === SessionToken::EXCEPTION_EXPIRED;
        if ($this->isApiRequest($request)) {
            // AJAX, return HTTP exception
            throw new HttpException(
                $e->getMessage(),
                $isExpired ? Response::HTTP_FORBIDDEN : Response::HTTP_BAD_REQUEST
            );
        }

        return $this->tokenRedirect($request);
    }

    /**
     * Handle a shop that is not installed or it's data is invalid.
     *
     * @param Request $request The request object.
     *
     * @throws HttpException If an AJAX/JSON request.
     *
     * @return mixed
     */
    protected function handleInvalidShop(Request $request)
    {
        if ($this->isApiRequest($request)) {
            // AJAX, return HTTP exception
            throw new HttpException('Shop is not installed or missing data.', Response::HTTP_FORBIDDEN);
        }

        return $this->installRedirect(ShopDomain::fromRequest($request));
    }

    /**
     * Login and verify the shop and it's data.
     *
     * @param SessionToken      $token     The session token.
     * @param NullableSessionId $sessionId Incoming session ID (if available).
     *
     * @return bool
     */
    protected function loginShopFromToken(SessionToken $token, NullableSessionId $sessionId): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($token->getShopDomain(), [], true);
        if (! $shop) {
            return false;
        }

        // Set the session details for the token, session ID, and access token
        $context = new SessionContext($token, $sessionId, $shop->getAccessToken());
        $shop->setSessionContext($context);

        $previousContext = $this->previousShop ? $this->previousShop->getSessionContext() : null;
        if (! $shop->getSessionContext()->isValid($previousContext)) {
            // Something is invalid
            return false;
        }

        // All is well, login the shop
        $this->auth->login($shop);

        return true;
    }

    /**
     * Redirect to token route.
     *
     * @param Request $request The request object.
     *
     * @return RedirectResponse
     */
    protected function tokenRedirect(Request $request): RedirectResponse
    {
        // At this point the HMAC and other details are verified already, filter it out
        $path = $request->path();
        $target = Str::start($path, '/');

        if ($request->query()) {
            $filteredQuery = Collection::make($request->query())->except([
                'hmac',
                'locale',
                'new_design_language',
                'timestamp',
                'session',
                'shop',
            ]);

            if ($filteredQuery->isNotEmpty()) {
                $target .= '?'.http_build_query($filteredQuery->toArray());
            }
        }

        return Redirect::route(
            Util::getShopifyConfig('route_names.authenticate.token'),
            [
                'shop' => ShopDomain::fromRequest($request)->toNative(),
                'target' => $target,
            ]
        );
    }

    /**
     * Redirect to install route.
     *
     * @param ShopDomainValue $shopDomain The shop domain.
     *
     * @return RedirectResponse
     */
    protected function installRedirect(ShopDomainValue $shopDomain): RedirectResponse
    {
        return Redirect::route(
            Util::getShopifyConfig('route_names.authenticate'),
            ['shop' => $shopDomain->toNative()]
        );
    }

    /**
     * Get the token from request (if available).
     *
     * @param Request $request The request object.
     *
     * @return string
     */
    protected function getAccessTokenFromRequest(Request $request): ?string
    {
        if (Util::getShopifyConfig('turbo_enabled')) {
            if ($request->bearerToken()) {
                // Bearer tokens collect.
                // Turbo does not refresh the page, values are attached to the same header.
                $bearerTokens = Collection::make(explode(',', $request->header('Authorization', '')));
                $newestToken = Str::substr(trim($bearerTokens->last()), 7);

                return $newestToken;
            }

            return $request->get('token');
        }

        return $this->isApiRequest($request) ? $request->bearerToken() : $request->get('token');
    }

    /**
     * Determine if the request is AJAX or expects JSON.
     *
     * @param Request $request The request object.
     *
     * @return bool
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->ajax() || $request->expectsJson();
    }

    /**
     * Check if there is a store record in the database.
     *
     * @param Request $request The request object.
     *
     * @return bool
     */
    protected function checkPreviousInstallation(Request $request): bool
    {
        $shop = $this->shopQuery->getByDomain(ShopDomain::fromRequest($request), [], true);

        return $shop && ! $shop->trashed();
    }
}
