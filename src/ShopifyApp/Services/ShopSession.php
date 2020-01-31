<?php

namespace OhMyBrew\ShopifyApp\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Traits\ShopAccessible;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
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
     * Constructor for shop session class.
     *
     * @param IShopCommand $shopCommand The commands for shop.
     *
     * @return self
     */
    public function __construct(IShopCommand $shopCommand)
    {
        $this->shopCommand = $shopCommand;
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
        $this->fixLifetime();
        Session::put(self::DOMAIN, $shopDomain);

        return $this;
    }

    /**
     * Gets the Shopify domain in session.
     *
     * @return string
     */
    public function getDomain(): string
    {
        return Session::get(self::DOMAIN);
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

            $this->fixLifetime();
            Session::put(self::USER, $this->user);
            Session::put(self::TOKEN, $token);
        } else {
            // Offline
            $this->shopCommand->setAccessToken($this->shop->id, $token);
        }

        return $this;
    }

    /**
     * Gets the access token in use.
     *
     * @param bool $strict Return the token matching the grant type (default: use either).
     *
     * @return string
     */
    public function getToken(bool $strict = false): string
    {
        // Tokens
        $peruser = AuthMode::PERUSER()->toNative();
        $offline = AuthMode::OFFLINE()->toNative();

        $tokens = [
            $peruser => Session::get(self::TOKEN),
            $offline => $this->shop->{self::TOKEN},
        ];

        if ($strict) {
            // We need the token matching the type
            return $tokens[$this->getType()];
        }

        // We need a token either way...
        return $tokens[$peruser] ?? $tokens[$offline];
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
        $result = !empty($this->getToken(true))
            && $this->getDomain() !== null
            && $this->getDomain() == $this->shop->shopify_domain;

        return $result;
    }

    /**
     * Fixes the lifetime of the session.
     *
     * @return void
     */
    protected function fixLifetime(): void
    {
        Config::set('session.expire_on_close', true);
    }
}
