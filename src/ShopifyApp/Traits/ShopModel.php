<?php

namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OhMyBrew\ShopifyApp\Storage\Scopes\Namespacing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;

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
     * {@inheritDoc}
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
     * {@inheritDoc}
     */
    public function isGrandfathered(): bool
    {
        return ((bool) $this->shopify_grandfathered) === true;
    }

    /**
     * {@inheritDoc}
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * {@inheritDoc}
     */
    public function hasCharges(): bool
    {
        return $this->charges->isNotEmpty();
    }

    /**
     * {@inheritDoc}
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * {@inheritDoc}
     */
    public function isFreemium(): bool
    {
        return ((bool) $this->shopify_freemium) === true;
    }

    /**
     * {@inheritDoc}
     */
    public function planCharge(NullablePlanId $planId = null): ?ChargeModel
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
     * {@inheritDoc}
     */
    public function hasOfflineAccess(): bool
    {
        return !empty($this->password);
    }
}
