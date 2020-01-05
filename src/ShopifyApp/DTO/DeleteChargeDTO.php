<?php

namespace OhMyBrew\ShopifyApp\DTO;

/**
 * Reprecents delete charge.
 */
class DeleteChargeDTO
{
    /**
     * The shop's ID.
     *
     * @var int
     */
    public $shopId;

    /**
     * The charge ID form Shopify.
     *
     * @var int
     */
    public $chargeId;
}
