<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Storage\Scopes\Namespacing;

/**
 * Responsible for reprecenting a shop record.
 */
trait ShopModel
{
    use SoftDeletes;

    /**
     * The API instance.
     *
     * @var BasicShopifyAPI
     */
    protected $api;

    /**
     * The session instance.
     *
     * @var ShopSession
     */
    protected $session;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'shopify_grandfathered' => 'bool',
        'shopify_freemium'      => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot(): void
    {
        parent::boot();

        static::addGlobalScope(new Namespacing());
    }

    /**
     * Creates or returns an instance of API for the shop.
     *
     * @return BasicShopifyAPI
     */
    public function api($session = ShopSession::class): BasicShopifyAPI
    {
        if (!$this->api) {
            // Get the domain and token
            $shopDomain = new ShopDomain($this->username);
            $token = (new $session())->getToken();

            // Create new API instance
            $this->api = ShopifyApp::api();
            $this->api->setSession($shopDomain->toNative(), $token->toNative());
        }

        // Return existing instance
        return $this->api;
    }

    /**
     * Checks is shop is grandfathered in.
     *
     * @return bool
     */
    public function isGrandfathered(): bool
    {
        return ((bool) $this->shopify_grandfathered) === true;
    }

    /**
     * Get charges.
     *
     * @return HasMany
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * Checks if charges have been applied to the shop.
     *
     * @return bool
     */
    public function hasCharges(): bool
    {
        return $this->charges->isNotEmpty();
    }

    /**
     * Gets the plan.
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Checks if the shop is freemium.
     *
     * @return bool
     */
    public function isFreemium(): bool
    {
        return ((bool) $this->shopify_freemium) === true;
    }

    /**
     * Gets the last single or recurring charge for the shop.
     * TODO: Move to command.
     *
     * @param NullablePlanId $planId The plan ID to check with.
     *
     * @return null|Charge
     */
    public function planCharge(NullablePlanId $planId = null)
    {
        return $this
            ->charges()
            ->withTrashed()
            ->whereIn('type', [ChargeType::RECURRING()->toNative(), ChargeType::ONETIME()->toNative()])
            ->where('plan_id', $planId ?? $this->plan_id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Checks if the access token is filled.
     *
     * @return bool
     */
    public function hasOfflineAccess(): bool
    {
        return !empty($this->shopify_token);
    }
}
