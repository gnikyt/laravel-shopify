<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Services\ShopSession;

/**
 * Reprecents the shop model.
 */
interface IShopModel
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
     * @param int|null $planId The plan ID to check with.
     *
     * @return Charge
     */
    public function planCharge(int $planId = null): ?Charge;
}
