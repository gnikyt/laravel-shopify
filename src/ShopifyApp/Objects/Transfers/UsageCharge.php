<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;

/**
 * Reprecents create usage charge.
 */
final class UsageCharge extends AbstractTransfer
{
    /**
     * The shop ID.
     *
     * @var ShopId
     */
    public $shopId;

    /**
     * The plan ID.
     *
     * @var PlanId
     */
    public $planId;

    /**
     * The charge ID from Shopify.
     *
     * @var ChargeId
     */
    public $chargeId;

    /**
     * Usage charge status.
     *
     * @var string
     */
    public $chargeStatus;

    /**
     * Usage charge price.
     *
     * @var float
     */
    public $price;

    /**
     * Usage charge description.
     *
     * @var string
     */
    public $description;

    /**
     * When the charge will be billed on.
     *
     * @var Carbon
     */
    public $billingOn;
}
