<?php

namespace OhMyBrew\ShopifyApp\DTO;

/**
 * Reprecents setting a plan for a shop.
 */
class ShopSetPlanDTO
{
    /**
     * Shop ID.
     *
     * @var int
     */
    public $shopId;

    /**
     * Plan ID.
     *
     * @var int
     */
    public $planId;
}