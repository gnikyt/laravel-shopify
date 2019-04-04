<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Scopes\NamespaceScope;
use OhMyBrew\ShopifyApp\Services\ShopSession;

/**
 * Responsible for reprecenting a shop record.
 */
trait ShopModelTrait
{
    use SoftDeletes;

    /**
     * The API instance.
     *
     * @var \OhMyBrew\BasicShopifyAPI
     */
    protected $api;

    /**
     * The session instance.
     *
     * @var \OhMyBrew\ShopifyApp\Services\ShopSession
     */
    protected $session;

    /**
     * Constructor for the model.
     *
     * @param array $attributes The model attribues to pass in.
     *
     * @return self
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new NamespaceScope());
    }

    /**
     * Creates or returns an instance of session for the shop.
     *
     * @return \OhMyBrew\ShopifyApp\Services\ShopSession
     */
    public function session()
    {
        if (!$this->session) {
            // Create new session instance
            $this->session = new ShopSession($this);
        }

        // Return existing instance
        return $this->session;
    }

    /**
     * Creates or returns an instance of API for the shop.
     *
     * @return \OhMyBrew\BasicShopifyAPI
     */
    public function api()
    {
        if (!$this->api) {
            // Get the domain and token
            $shopDomain = $this->shopify_domain;
            $token = $this->session()->getToken();

            // Create new API instance
            $this->api = ShopifyApp::api();
            $this->api->setSession($shopDomain, $token);
        }

        // Return existing instance
        return $this->api;
    }

    /**
     * Checks is shop is grandfathered in.
     *
     * @return bool
     */
    public function isGrandfathered()
    {
        return ((bool) $this->grandfathered) === true;
    }

    /**
     * Get charges.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * Checks if charges have been applied to the shop.
     *
     * @return bool
     */
    public function hasCharges()
    {
        return $this->charges->isNotEmpty();
    }

    /**
     * Gets the plan.
     *
     * @return \OhMyBrew\ShopifyApp\Models\Plan
     */
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Checks if the shop is freemium.
     *
     * @return bool
     */
    public function isFreemium()
    {
        return ((bool) $this->freemium) === true;
    }

    /**
     * Gets the last single or recurring charge for the shop.
     *
     * @return null|\OhMyBrew\ShopifyApp\Models\Charge
     */
    public function planCharge()
    {
        return $this->charges()
            ->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])
            ->where('plan_id', $this->plan_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Checks if the access token is filled.
     *
     * @return bool
     */
    public function hasOfflineAccess()
    {
        return !empty($this->shopify_token);
    }
}
