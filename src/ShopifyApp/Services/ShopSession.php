<?php

namespace OhMyBrew\ShopifyApp\Services;

use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Session;
use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\NullableAccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

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
     * The (session/database) key for Shopify access token.
     *
     * @var string
     */
    public const USER_TOKEN = 'shopify_token';

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
     * @return self
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
     * @return self
     */
    public function make(ShopDomain $domain): self
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain($domain, [], true);

        // Log them in with the guard
        $this->auth->guard()->login($shop);

        return $this;
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
     * @param object $access
     *
     * @return self
     */
    public function setAccess(object $access): self
    {
        // Grab the token
        $token = new AccessToken($access->access_token);

        // Per-User
        if (property_exists($access, 'associated_user')) {
            // We have a user, so access will live only in session
            $this->sessionSet(self::USER, $access->associated_user);
            $this->sessionSet(self::USER_TOKEN, $token->toNative());
        } else {
            // Update the token in database
            $this->shopCommand->setAccessToken($this->shop()->getId(), $token);

            // Refresh the model
            $this->shop()->refresh();
        }

        return $this;
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
            $offline => $this->shop()->getToken(),
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
     * @return object|null
     */
    public function getUser(): ?object
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
     * Forgets anything in session.
     * Log out a shop via auth()->guard()->logout().
     *
     * @return self
     */
    public function forget(): self
    {
        // Forget session values
        $keys = [self::USER, self::USER_TOKEN];
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
     * @param IShopModel $shop The shop to compare validity to.
     *
     * @return bool
     */
    public function isValid(IShopModel $shop): bool
    {
        // Check if there is an existing session
        $currentShop = $this->shop();
        if ($currentShop) {
            // We have a current shop, validate it and compare domains
            $currentToken = $this->getToken(true);
            $currentDomain = $currentShop->getDomain();

            return !$currentToken->isEmpty() &&
                !$currentDomain->isNull() &&
                $currentDomain->isSame($shop->getDomain());
        }

        // No current shop, validate it and skip compare of domains
        return !$shop->getToken()->isEmpty() && !$shop->getDomain()->isNull();
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

    /**
     * Wrapper for auth->guard()->user().
     *
     * @return IShopModel|null
     */
    protected function shop(): ?IShopModel
    {
        return $this->auth->guard()->user();
    }
}
