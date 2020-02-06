<?php

namespace OhMyBrew\ShopifyApp\Services;

use OhMyBrew\BasicShopifyAPI;
use Illuminate\Auth\AuthManager;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\NullShopDomain;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;

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
     * @param IApiHelper    $apiHelper    The API helper.
     * @param CookieHelper  $cookieHelper The cookie helper.
     * @param AuthManager   $auth        The Laravel auth manager.
     * @param IShopCommand  $shopCommand  The commands for shop.
     *
     * @return self
     */
    public function __construct(
        IApiHelper $apiHelper,
        CookieHelper $cookieHelper,
        AuthManager $auth,
        IShopCommand $shopCommand
    ) {
        $this->apiHelper = $apiHelper;
        $this->cookieHelper = $cookieHelper;
        $this->auth = $auth;
        $this->shopCommand = $shopCommand;
    }

    /**
     * Wrapper for auth->user().
     *
     * @return IShopModel|null
     */
    public function getShop(): ?IShopModel
    {
        return $this->auth->guard()->user();
    }

    /**
     * Wrapper for checking if getSession is valid.
     *
     * @return bool
     */
    public function hasSession(): bool
    {
        return $this->getShop() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function api(): BasicShopifyAPI
    {
        if (!$this->api) {
            // Get the shop
            $shop = $this->getShop();

            // Create new API instance
            $this->api = $this->apiHelper->createApi();
            $this->api->setSession(
                $shop->getDomain()->toNative(),
                $this->getToken()->toNative()
            );
        }

        // Return existing instance
        return $this->api;
    }

    /**
     * Determines the type of access.
     *
     * @return string
     */
    public function getType(): AuthMode
    {
        $peruser = AuthMode::PERUSER();
        $offline = AuthMode::OFFLINE();

        if (AuthMode::fromNative($this->getConfig('api_grant_mode'))->isSame($peruser)) {
            return $peruser;
        }

        return $offline;
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
            // Offline
            $this->shopCommand->setAccessToken($this->getShop()->getId(), $token);
        }

        return $this;
    }

    /**
     * Gets the access token in use.
     *
     * @param bool $strict Return the token matching the grant type (default: use either).
     *
     * @return AccessToken
     */
    public function getToken(bool $strict = false): AccessToken
    {
        // Keys as strings
        $peruser = AuthMode::PERUSER()->toNative();
        $offline = AuthMode::OFFLINE()->toNative();

        // Token mapping
        $tokens = [
            $peruser => new AccessToken(Session::get(self::USER_TOKEN)),
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
        $this->guard->logout();

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
        // Grab the domain and token for comparison
        $currentShop = $this->getShop();
        $currentToken = $this->getToken(true);
        $currentDomain = $currentShop ? $currentShop->getDomain() : new NullShopDomain();

        // No token set or domain in session?
        return !$currentToken->isEmpty() && !$currentDomain->isNull() && $currentDomain->isSame($shop->getDomain());
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
