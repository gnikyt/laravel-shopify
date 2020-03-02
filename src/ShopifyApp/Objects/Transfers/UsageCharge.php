<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails;

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
     * @var ChargeReference
     */
    public $chargeReference;

    /**
     * Usage charge type.
     *
     * @var ChargeType
     */
    public $chargeType;

    /**
     * Usage charge status.
     *
     * @var ChargeStatus
     */
    public $chargeStatus;

    /**
     * When the charge will be billed on.
     *
     * @var Carbon
     */
    public $billingOn;

    /**
     * Usage charge details.
     *
     * @var UsageChargeDetails
     */
    public $details;

    /**
     * Constructor.
     *
     * @return self
     */
    public function __construct()
    {
        $this->chargeType = ChargeType::USAGE();
        $this->chargeStatus = ChargeStatus::ACCEPTED();
    }
}
