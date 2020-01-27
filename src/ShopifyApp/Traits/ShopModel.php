<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Scopes\Namespacing;
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
        'grandfathered' => 'bool',
        'freemium'      => 'bool',
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
            $shopDomain = $this->username;
            $token = (new $session)->getToken();

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
    public function isGrandfathered(): bool
    {
        return ((bool) $this->grandfathered) === true;
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
        return ((bool) $this->freemium) === true;
    }

    /**
     * Gets the last single or recurring charge for the shop.
     * TODO: Move to command.
     *
     * @param int|null $planId The plan ID to check with.
     *
     * @return null|Charge
     */
    public function planCharge(int $planId = null)
    {
        return $this
            ->charges()
            ->withTrashed()
            ->whereIn('type', [Charge::CHARGE_RECURRING, Charge::CHARGE_ONETIME])
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
