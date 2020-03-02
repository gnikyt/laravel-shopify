<?php

namespace Osiset\ShopifyApp\Contracts;

use Osiset\BasicShopifyAPI;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Reprecents the shop model.
 */
interface ShopModel extends Authenticatable
{
    /**
     * Get shop ID as a value object.
     *
     * @return ShopId
     */
    public function getId(): ShopId;

    /**
     * Get shop domain as a value object.
     *
     * @return ShopDomainValue;
     */
    public function getDomain(): ShopDomainValue;

    /**
     * Get shop access token as a value object.
     *
     * @return AccessTokenValue
     */
    public function getToken(): AccessTokenValue;

    /**
     * Gets charges belonging to the shop.
     *
     * @return HasMany
     */
    public function charges(): HasMany;

    /**
     * Gets the plan the shop is tied to.
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo;

    /**
     * Checks is shop is grandfathered in.
     *
     * @return bool
     */
    public function isGrandfathered(): bool;

    /**
     * Checks if the shop is freemium.
     *
     * @return bool
     */
    public function isFreemium(): bool;

    /**
     * Checks if the access token is filled.
     *
     * @return bool
     */
    public function hasOfflineAccess(): bool;

    /**
     * Get the API helper instance for a shop.
     * TODO: Find a better way than using resolve(). However, we can't inject in model constructors.
     *
     * @return IApiHelper
     */
    public function apiHelper(): IApiHelper;

    /**
     * Get the API instance.
     *
     * @return BasicShopifyAPI
     */
    public function api(): BasicShopifyAPI;
}
