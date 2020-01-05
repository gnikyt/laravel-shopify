<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use OhMyBrew\ShopifyApp\DTO\ShopSetPlanDTO;

/**
 * Reprecents commands for shops.
 */
interface IShopCommand
{
    /**
     * Sets a plan to a shop, meanwhile cancelling freemium.
     *
     * @param ShopSetPlanDTO $setObj The data needed for setting the plan to the shop.
     *
     * @return bool
     */
    public function setToPlan(ShopSetPlanDTO $setObj): bool;
}
