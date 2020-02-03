<?php

namespace OhMyBrew\ShopifyApp\Contracts;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Services\ShopSession;
use OhMyBrew\ShopifyApp\Storage\Models\Charge;

/**
 * Reprecents the shop model.
 */
interface ShopModel
{
    /**
     * Creates or returns an instance of session for the shop.
     *
     * @return ShopSession
     */
    public function session(): ShopSession;

    /**
     * Creates or returns an instance of API for the shop.
     *
     * @return BasicShopifyAPI
     */
    public function api(): BasicShopifyAPI;

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
     * Gets the last single or recurring charge for the shop.
     *
     * @param NullablePlanId|null $planId The plan ID to check with.
     *
     * @return Charge
     */
    public function planCharge(NullablePlanId $planId = null): ?Charge;
}
