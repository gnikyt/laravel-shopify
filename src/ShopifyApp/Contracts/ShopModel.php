<?php

namespace OhMyBrew\ShopifyApp\Contracts;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;

/**
 * Reprecents the shop model.
 */
interface ShopModel
{
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
     * @return ChargeModel
     */
    public function planCharge(NullablePlanId $planId = null): ?ChargeModel;
}
