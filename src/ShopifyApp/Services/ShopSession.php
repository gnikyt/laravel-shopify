<?php

namespace OhMyBrew\ShopifyApp\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Shop;
use stdClass;

/**
 * Responsible for handling session retreival and storage.
 */
class ShopSession
{
    /**
     * The session key for Shopify domain.
     *
     * @var string
     */
    const DOMAIN = 'shopify_domain';

    /**
     * The session key for Shopify associated user.
     *
     * @var string
     */
    const USER = 'shopify_user';

    /**
     * The (session/database) key for Shopify access token.
     *
     * @var string
     */
    const TOKEN = 'shopify_token';

    /**
     * The offline grant key.
     *
     * @var string
     */
    const GRANT_OFFLINE = 'offline';

    /**
     * The per-user grant key.
     *
     * @var string
     */
    const GRANT_PERUSER = 'per-user';

    /**
     * The shop.
     *
     * @var \OhMyBrew\ShopifyApp\Models\Shop
     */
    protected $shop;

    /**
     * Constructor for shop session class.
     *
     * @param \OhMyBrew\ShopifyApp\Models\Shop|null $shop The shop.
     *
     * @return self
     */
    public function __construct(Shop $shop = null)
    {
        $this->shop = $shop;
    }

    /**
     * Determines the type of access.
     *
     * @return string
     */
    public function getType()
    {
        if ($this->hasUser()) {
            return self::GRANT_PERUSER;
        }

        return self::GRANT_OFFLINE;
    }

    /**
     * Determines if the type of access matches.
     *
     * @param string $type The type of access to check.
     *
     * @return string
     */
    public function isType(string $type)
    {
        return $this->getType() === $type;
    }

    /**
     * Sets the Shopify domain to session.
     * `expire_on_close` must be set to avoid issue of cookies being deleted too early.
     *
     * @param string $shopDomain The Shopify domain.
     *
     * @return void
     */
    public function setDomain(string $shopDomain)
    {
        $this->fixLifetime();
        Session::put(self::DOMAIN, $shopDomain);
    }

    /**
     * Gets the Shopify domain in session.
     *
     * @return void
     */
    public function getDomain()
    {
        return Session::get(self::DOMAIN);
    }

    /**
     * Stores the access token and user (if any).
     * Uses database for acess token if it was an offline authentication.
     *
     * @param stdClass $access
     *
     * @return void
     */
    public function setAccess(stdClass $access)
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

            return;
        }

        // Offline
        $this->shop->{self::TOKEN} = $token;
        $this->shop->save();
    }

    /**
     * Gets the access token in use.
     *
     * @return string
     */
    public function getToken()
    {
        // Offline token
        $shopToken = $this->shop->{self::TOKEN};

        // Per-user token
        $puToken = null;
        if ($this->isType(self::GRANT_PERUSER)) {
            $puToken = Session::get(self::TOKEN);
        }

        return $puToken ?: $shopToken;
    }

    /**
     * Gets the associated user (if any).
     *
     * @return stfClass|null
     */
    public function getUser()
    {
        return Session::get(self::USER);
    }

    /**
     * Determines if there is an associated user.
     *
     * @return bool
     */
    public function hasUser()
    {
        return $this->getUser() !== null;
    }

    /**
     * Forgets anything in session.
     *
     * @return void
     */
    public function forget()
    {
        $keys = [self::DOMAIN, self::USER, self::TOKEN];
        foreach ($keys as $key) {
            Session::forget($key);
        }
    }

    /**
     * Fixes the lifetime of the session.
     *
     * @return void
     */
    protected function fixLifetime()
    {
        Config::set('session.expire_on_close', true);
    }
}
