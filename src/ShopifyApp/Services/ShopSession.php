<?php

namespace OhMyBrew\ShopifyApp\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Traits\ShopAccessible;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\NullableShopDomain;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;

/**
 * Responsible for handling session retreival and storage.
 */
class ShopSession
{
    use ShopAccessible;

    /**
     * The session key for Shopify domain.
     *
     * @var string
     */
    public const DOMAIN = 'shopify_domain';

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
    public const TOKEN = 'shopify_token';

    /**
     * The commands for shop.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * The cookie helper.
     *
     * @var CookieHelper
     */
    protected $cookieHelper;

    /**
     * Constructor for shop session class.
     *
     * @param IShopCommand $shopCommand  The commands for shop.
     * @param CookieHelper $cookieHelper The cookie helper.
     *
     * @return self
     */
    public function __construct(IShopCommand $shopCommand, CookieHelper $cookieHelper)
    {
        $this->shopCommand = $shopCommand;
        $this->cookieHelper = $cookieHelper;
    }

    /**
     * Determines the type of access.
     *
     * @return string
     */
    public function getType(): string
    {
        $config = Config::get('shopify-app.api_grant_mode');
        if ($config === AuthMode::PERUSER()->toNative()) {
            return AuthMode::PERUSER()->toNative();
        }

        return AuthMode::OFFLINE()->toNative();
    }

    /**
     * Determines if the type of access matches.
     *
     * @param string $type The type of access to check.
     *
     * @return bool
     */
    public function isType(string $type): bool
    {
        return $this->getType() === $type;
    }

    /**
     * Sets the Shopify domain to session.
     * `expire_on_close` must be set to avoid issue of cookies being deleted too early.
     *
     * @param ShopDomain $shopDomain The Shopify domain.
     *
     * @return self
     */
    public function setDomain(ShopDomain $shopDomain): self
    {
        return $this->sessionSet(self::DOMAIN, $shopDomain);
    }

    /**
     * Gets the Shopify domain in session.
     *
     * @return NullableShopDomain
     */
    public function getDomain(): NullableShopDomain
    {
        return new NullableShopDomain(
            Session::get(self::DOMAIN)
        );
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
        $token = $access->access_token;

        // Per-User
        if (property_exists($access, 'associated_user')) {
            // We have a user, so access will live only in session
            $this->user = $access->associated_user;

            $this->sessionSet(self::USER, $this->user);
            $this->sessionSet(self::TOKEN, $this->token);
        } else {
            // Offline
            $this->shopCommand->setAccessToken(new ShopId($this->shop->id), new AccessToken($token));
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
        // Tokens
        $peruser = AuthMode::PERUSER()->toNative();
        $offline = AuthMode::OFFLINE()->toNative();

        $tokens = [
            $peruser => new AccessToken(Session::get(self::TOKEN)),
            $offline => new AccessToken($this->shop->{self::TOKEN}),
        ];

        if ($strict) {
            // We need the token matching the type
            return $tokens[$this->getType()];
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
        $keys = [self::DOMAIN, self::USER, self::TOKEN];
        foreach ($keys as $key) {
            Session::forget($key);
        }

        return $this;
    }

    /**
     * Checks if the package has everything it needs in session.
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // No token set or domain in session?
        $result = !empty($this->getToken(true)->toNative())
            && !$this->getDomain()->isNull()
            && $this->getDomain()->toNative() == $this->shop->shopify_domain;

        return $result;
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
