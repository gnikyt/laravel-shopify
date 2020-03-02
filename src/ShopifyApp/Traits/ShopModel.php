<?php

namespace OhMyBrew\ShopifyApp\Traits;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Storage\Scopes\Namespacing;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Objects\Transfers\ApiSession as ApiSessionTransfer;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Responsible for reprecenting a shop record.
 */
trait ShopModel
{
    use SoftDeletes;

    /**
     * The API helper instance.
     *
     * @var IApiHelper
     */
    public $apiHelper;

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
     * {@inheritdoc}
     */
    public function getId(): ShopId
    {
        return new ShopId($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain(): ShopDomainValue
    {
        return new ShopDomain($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): AccessTokenValue
    {
        return new AccessToken($this->password);
    }

    /**
     * {@inheritdoc}
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * {@inheritdoc}
     */
    public function hasCharges(): bool
    {
        return $this->charges->isNotEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * {@inheritdoc}
     */
    public function isGrandfathered(): bool
    {
        return ((bool) $this->shopify_grandfathered) === true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFreemium(): bool
    {
        return ((bool) $this->shopify_freemium) === true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOfflineAccess(): bool
    {
        return !$this->getToken()->isNull();
    }

    /**
     * {@inheritdoc}
     */
    public function apiHelper(): IApiHelper
    {
        if ($this->apiHelper === null) {
            // Set the session
            $session = new ApiSessionTransfer();
            $session->domain = $this->getDomain();
            $session->token = $this->getToken();

            $this->apiHelper = resolve(IApiHelper::class)->make($session);
        }

        return $this->apiHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function api(): BasicShopifyAPI
    {
        if ($this->apiHelper === null) {
            $this->apiHelper();
        }

        return $this->apiHelper->getApi();
    }
}
