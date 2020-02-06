<?php

namespace OhMyBrew\ShopifyApp\Contracts\Commands;

use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId as PlanIdValue;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;

/**
 * Reprecents commands for shops.
 */
interface Shop
{
    /**
     * Create a shop.
     *
     * @return ShopId
     */
    public function make(ShopDomainValue $domain, AccessTokenValue $token): ShopId;

    /**
     * Sets a plan to a shop, meanwhile cancelling freemium.
     *
     * @param ShopId      $shopId The shop's ID.
     * @param PlanIdValue $planId The plan's ID.
     *
     * @return bool
     */
    public function setToPlan(ShopId $shopId, PlanIdValue $planId): bool;

    /**
     * Sets the access token (offline) from Shopify to the shop.
     *
     * @param ShopId           $shopId The shop's ID.
     * @param AccessTokenValue $token  The token from Shopify Oauth.
     *
     * @return bool
     */
    public function setAccessToken(ShopId $shopId, AccessTokenValue $token): bool;

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

    /**
     * Set a shop as freemium.
     *
     * @param ShopId $shopId The shop's ID.
     *
     * @return bool
     */
    public function setAsFreemium(ShopId $shopId): bool;

    /**
     * Set a shop to a namespace.
     *
     * @param ShopId $shopId    The shop's ID.
     * @param string $namespace The namespace.
     *
     * @return bool
     */
    public function setNamespace(ShopId $shopId, string $namespace): bool;
}
