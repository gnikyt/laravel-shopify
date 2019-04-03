<?php

namespace OhMyBrew\ShopifyApp\Services;

use stdClass;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use OhMyBrew\ShopifyApp\Models\Shop;

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
     * This associated user (if any).
     *
     * @var stdClass
     */
    protected $user;

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
        if ($this->user !== null) {
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
     
     * @return void
     */
    public function setDomain(string $shopDomain)
    {
        Config::set('session.expire_on_close', true);
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

            Session::put(self::USER, $this->user);
            Session::put(self::TOKEN, $token);

            return;
        }

        // Offline
        $this->shop->{self::TOKEN} = $token;
        $this->shop->save();

        return;
    }

    /**
     * Gets the access token in use.
     *
     * @return string
     */
    public function getToken()
    {
        // Per-User
        if ($this->isType(self::GRANT_PERUSER)) {
            return Session::get(self::TOKEN);
        }

        // Offline
        return $this->shop->{self::TOKEN};
    }

    /**
     * Gets the associated user (if any).
     *
     * @return stfClass|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Determines if there is an associated user.
     *
     * @return bool
     */
    public function hasUser()
    {
        return $this->user !== null;
    }

    /**
     * Forgets anything in session.
     *
     * @return void
     */
    public function forget()
    {
        Session::forget(self::DOMAIN);
        Session::forget(self::USER);
    }
}