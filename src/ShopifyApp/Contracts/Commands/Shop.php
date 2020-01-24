<?php

namespace OhMyBrew\ShopifyApp\Contracts\Commands;

use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Reprecents commands for shops.
 */
interface Shop
{
    /**
     * Sets a plan to a shop, meanwhile cancelling freemium.
     *
     * @param ShopId   $shopId The shop's ID.
     * @param ChargeId $planId The plan's ID.
     *
     * @return bool
     */
    public function setToPlan(ShopId $shopId, PlanId $planId): bool;

    /**
     * Sets the access token (offline) from Shopify to the shop.
     *
     * @param ShopId      $shopId The shop's ID.
     * @param AccessToken $token  The token from Shopify Oauth.
     *
     * @return bool
     */
    public function setAccessToken(ShopId $shopId, AccessToken $token): bool;

    /**
     * Cleans the shop's properties (token, plan).
     * Used for uninstalls.
     *
     * @param ShopId $shopId The shop's ID.
     *
     * @return bool
     */
    public function clean(ShopId $shopId): bool;

    /**
     * Soft deletes a shop.
     * Used for uninstalls.
     *
     * @param ShopId $shopId The shop's ID.
     *
     * @return bool
     */
    public function softDelete(ShopId $shopId): bool;

    /**
     * Restore a soft-deleted shop.
     *
     * @param ShopId $shopId The shop's ID.
     *
     * @return bool
     */
    public function restore(ShopId $shopId): bool;
}
