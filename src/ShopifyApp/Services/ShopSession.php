<?php

namespace Osiset\ShopifyApp\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Values\AccessToken;
use Osiset\ShopifyApp\Objects\Values\NullableAccessToken;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

/**
 * Responsible for handling session retreival and storage.
 */
class ShopSession
{
    use ConfigAccessible;

    /**
     * The session key for Shopify associated user.
     *
     * @var string
     */
    public const USER = 'shopify_user';

    /**
     * The session key for Shopify user access token.
     *
     * @var string
     */
    public const USER_TOKEN = 'shopify_user_token';

    /**
     * The session key for Shopify user expiration.
     *
     * @var string|null
     */
    public const USER_EXPIRES = 'shopify_user_expires';

    /**
     * Session token from Shopify.
     *
     * @var string|null
     */
    public const SESSION_TOKEN = 'shopify_session_token';

    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The commands for shop.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * The queries for shop.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * The Laravel auth manager.
     *
     * @var AuthManager
     */
    protected $auth;

    /**
     * The cookie helper.
     *
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * API instance cached.
     *
     * @var BasicShopifyAPI
     */
    protected $api;

    /**
     * Constructor for shop session class.
     *
     * @param AuthManager  $auth         The Laravel auth manager.
     * @param IApiHelper   $apiHelper    The API helper.
     * @param CookieHelper $cookieHelper The cookie helper.
     * @param IShopCommand $shopCommand  The commands for shop.
     * @param IShopQuery   $shopQuery    The queries for shop.
     *
     * @return void
     */
    public function __construct(
        AuthManager $auth,
        IApiHelper $apiHelper,
        CookieHelper $cookieHelper,
        IShopCommand $shopCommand,
        IShopQuery $shopQuery
    ) {
        $this->auth = $auth;
        $this->apiHelper = $apiHelper;
        $this->cookieHelper = $cookieHelper;
        $this->shopCommand = $shopCommand;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Login a shop.
     *
     * @return bool
     */
    public function make(ShopDomainValue $domain): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($domain, [], true);
        if (! $shop) {
            return false;
        }

        // Log them in with the guard
        $this->cookieHelper->setCookiePolicy();
        $this->auth->guard()->login($shop);

        return true;
    }

    /**
     * Wrapper for auth->guard()->guest().
     *
     * @return bool
     */
    public function guest(): bool
    {
        return $this->auth->guard()->guest();
    }

    /**
     * Determines the type of access.
     *
     * @return string
     */
    public function getType(): AuthMode
    {
        return AuthMode::fromNative(strtoupper($this->getConfig('api_grant_mode')));
    }

    /**
     * Determines if the type of access matches.
     *
     * @param AuthMode $type The type of access to check.
     *
     * @return bool
     */
    public function isType(AuthMode $type): bool
    {
        return $this->getType()->isSame($type);
    }

    /**
     * Stores the access token and user (if any).
     * Uses database for acess token if it was an offline authentication.
     *
     * @param ResponseAccess $access
     *
     * @return self
     */
    public function setAccess(ResponseAccess $access): self
    {
        // Grab the token
        $token = AccessToken::fromNative($access['access_token']);

        // Per-User
        if (isset($access['associated_user'])) {
            // Modify the expire time to a timestamp
            $now = Carbon::now();
            $expires = $now->addSeconds($access['expires_in'] - 10);

            // We have a user, so access will live only in session
            $this->sessionSet(self::USER, $access['associated_user']);
            $this->sessionSet(self::USER_TOKEN, $token->toNative());
            $this->sessionSet(self::USER_EXPIRES, $expires->toDateTimeString());
        } else {
            // Update the token in database
            $this->shopCommand->setAccessToken($this->getShop()->getId(), $token);

            // Refresh the model
            $this->getShop()->refresh();
        }

        return $this;
    }

    /**
     * Sets the session token from Shopify.
     *
     * @param string $token The session token from Shopify.
     *
     * @return self
     */
    public function setSessionToken(string $token): self
    {
        $this->sessionSet(self::SESSION_TOKEN, $token);

        return $this;
    }

    /**
     * Get the Shopify session token.
     *
     * @return string|null
     */
    public function getSessionToken(): ?string
    {
        return Session::get(self::SESSION_TOKEN);
    }

    /**
     * Compare session tokens from Shopify.
     *
     * @param string|null $incomingToken The session token from Shopify, from the request.
     *
     * @return bool
     */
    public function isSessionTokenValid(?string $incomingToken): bool
    {
        $currentToken = $this->getSessionToken();
        if ($incomingToken === null || $currentToken === null) {
            return true;
        }

        return $incomingToken === $currentToken;
    }

    /**
     * Gets the access token in use.
     *
     * @param bool $strict Return the token matching the grant type (default: use either).
     *
     * @return AccessTokenValue
     */
    public function getToken(bool $strict = false): AccessTokenValue
    {
        // Keys as strings
        $peruser = AuthMode::PERUSER()->toNative();
        $offline = AuthMode::OFFLINE()->toNative();

        // Token mapping
        $tokens = [
            $peruser => NullableAccessToken::fromNative(Session::get(self::USER_TOKEN)),
            $offline => $this->getShop()->getToken(),
        ];

        if ($strict) {
            // We need the token matching the type
            return $tokens[$this->getType()->toNative()];
        }

        // We need a token either way...
        return $tokens[$peruser]->isNull() ? $tokens[$offline] : $tokens[$peruser];
    }

    /**
     * Gets the associated user (if any).
     *
     * @return ResponseAccess|null
     */
    public function getUser(): ?ResponseAccess
    {
        return Session::get(self::USER);
    }

    /**
     * Determines if there is an associated user.
     *
     * @return bool
     */
    public function hasUser(): bool
    {
        return $this->getUser() !== null;
    }

    /**
     * Check if the user has expired.
     *
     * @return bool
     */
    public function isUserExpired(): bool
    {
        $now = Carbon::now();
        $expires = new Carbon(Session::get(self::USER_EXPIRES));

        return $now->greaterThanOrEqualTo($expires);
    }

    /**
     * Forgets anything in session.
     * Log out a shop via auth()->guard()->logout().
     *
     * @return self
     */
    public function forget(): self
    {
        // Forget session values
        $keys = [self::USER, self::USER_TOKEN, self::USER_EXPIRES, self::SESSION_TOKEN];
        foreach ($keys as $key) {
            Session::forget($key);
        }

        // Logout the shop if logged in
        $this->auth->guard()->logout();

        return $this;
    }

    /**
     * Checks if the package has everything it needs in session.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        $currentShop = $this->getShop();
        $currentToken = $this->getToken(true);
        $currentDomain = $currentShop->getDomain();

        $baseValid = ! $currentToken->isEmpty() && ! $currentDomain->isNull();
        if ($this->getUser() !== null) {
            // Handle validation of per-user
            return $baseValid && ! $this->isUserExpired();
        }

        // Handle validation of standard
        return $baseValid;
    }

    /**
     * Checks if the package has everything it needs in session (compare).
     *
     * @param ShopDomain $shopDomain The shop to compare validity to.
     *
     * @return bool
     */
    public function isValidCompare(ShopDomain $shopDomain): bool
    {
        // Ensure domains match
        return $this->isValid() && $shopDomain->isSame($this->getShop()->getDomain());
    }

    /**
     * Wrapper for auth->guard()->user().
     *
     * @return IShopModel|null
     */
    public function getShop(): ?IShopModel
    {
        return $this->auth->guard()->user();
    }

    /**
     * Set a session key/value and fix cookie issues.
     *
     * @param string $key   The key.
     * @param mixed  $value The value.
     *
     * @return self
     */
    protected function sessionSet(string $key, $value): self
    {
        $this->cookieHelper->setCookiePolicy();
        Session::put($key, $value);

        return $this;
    }
}
