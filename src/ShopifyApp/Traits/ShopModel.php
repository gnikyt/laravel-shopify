<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Session;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;
use Osiset\ShopifyApp\Objects\Values\AccessToken;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Services\ShopSession;
use Osiset\ShopifyApp\Storage\Models\Charge;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Storage\Scopes\Namespacing;

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
     * Boot the trait.
     *
     * Note that the method boot[TraitName] is auotmatically booted by Laravel.
     *
     * @return void
     */
    protected static function bootShopModel(): void
    {
        static::addGlobalScope(new Namespacing());
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ShopIdValue
    {
        return ShopId::fromNative($this->id);
    }

    /**
     * {@inheritdoc}
     */
    public function getDomain(): ShopDomainValue
    {
        return ShopDomain::fromNative($this->name);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken(): AccessTokenValue
    {
        return AccessToken::fromNative($this->password);
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
        return (bool) $this->shopify_grandfathered === true;
    }

    /**
     * {@inheritdoc}
     */
    public function isFreemium(): bool
    {
        return (bool) $this->shopify_freemium === true;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOfflineAccess(): bool
    {
        return ! $this->getToken()->isNull() && ! empty($this->password);
    }

    /**
     * {@inheritdoc}
     */
    public function apiHelper(): IApiHelper
    {
        if ($this->apiHelper === null) {
            // Get the token
            /** @var ShopSession $shopSession */
            $shopSession = resolve(ShopSession::class);
            $token = $shopSession->guest() ? $this->getToken() : $shopSession->getToken();

            // Set the session
            $session = new Session(
                $this->getDomain()->toNative(),
                $token->toNative(),
                $shopSession->getUser()
            );
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
