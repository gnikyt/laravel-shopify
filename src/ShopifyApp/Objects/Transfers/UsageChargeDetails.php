<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;

/**
 * Reprecents details for a usage charge.
 */
final class UsageChargeDetails extends AbstractTransfer
{
    /**
     * The Shopify charge ID.
     *
     * @var ChargeId
     */
    public $chargeId;

    /**
     * Usage charge price.
     *
     * @var float
     */
    public $price;

    /**
     * Useage charge description.
     *
     * @var string
     */
    public $description;
}
