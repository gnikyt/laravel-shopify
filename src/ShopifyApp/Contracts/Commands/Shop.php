<?php

namespace Osiset\ShopifyApp\Contracts\Commands;

use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\PlanId as PlanIdValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;

/**
 * Reprecents commands for shops.
 */
interface Shop
{
    /**
     * Create a shop.
     *
     * @param  ShopDomainValue  $domain
     * @param  AccessTokenValue  $token
     *
     * @return ShopIdValue
     */
    public function make(ShopDomainValue $domain, AccessTokenValue $token): ShopIdValue;

    /**
     * Sets a plan to a shop, meanwhile cancelling freemium.
     *
     * @param ShopIdValue $shopId The shop's ID.
     * @param PlanIdValue $planId The plan's ID.
     *
     * @return bool
     */
    public function setToPlan(ShopIdValue $shopId, PlanIdValue $planId): bool;

    /**
     * Sets the access token (offline) from Shopify to the shop.
     *
     * @param ShopIdValue      $shopId The shop's ID.
     * @param AccessTokenValue $token  The token from Shopify Oauth.
     *
     * @return bool
     */
    public function setAccessToken(ShopIdValue $shopId, AccessTokenValue $token): bool;

    /**
     * Cleans the shop's properties (token, plan).
     * Used for uninstalls.
     *
     * @param ShopIdValue $shopId The shop's ID.
     *
     * @return bool
     */
    public function clean(ShopIdValue $shopId): bool;

    /**
     * Soft deletes a shop.
     * Used for uninstalls.
     *
     * @param ShopIdValue $shopId The shop's ID.
     *
     * @return bool
     */
    public function softDelete(ShopIdValue $shopId): bool;

    /**
     * Restore a soft-deleted shop.
     *
     * @param ShopIdValue $shopId The shop's ID.
     *
     * @return bool
     */
    public function restore(ShopIdValue $shopId): bool;

    /**
     * Set a shop as freemium.
     *
     * @param ShopIdValue $shopId The shop's ID.
     *
     * @return bool
     */
    public function setAsFreemium(ShopIdValue $shopId): bool;

    /**
     * Set a shop to a namespace.
     *
     * @param ShopIdValue $shopId    The shop's ID.
     * @param string      $namespace The namespace.
     *
     * @return bool
     */
    public function setNamespace(ShopIdValue $shopId, string $namespace): bool;
}
