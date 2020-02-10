<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Reprecents create usage charge.
 * TODO: Add properties for types.
 */
class UsageCharge extends AbstractTransfer
{
    /**
     * Constructor.
     *
     * @param ShopId   $shopId       Shop ID.
     * @param PlanId   $planId       Plan ID.
     * @param ChargeId $chargeId     Charge ID from Shopify.
     * @param string   $chargeStatus Usage charge status.
     * @param float    $price        Usage charge price.
     * @param string   $description  Usage charge description.
     * @param Carbon   $billingOn    When the charge will be billed on.
     *
     * @return self
     */
    public function __construct(
        ShopId $shopId,
        PlanId $planId,
        ChargeId $chargeId,
        string $chargeStatus,
        float $price,
        string $description,
        Carbon $billingOn
    ) {
        $this->data['shopId'] = $shopId;
        $this->data['planId'] = $planId;
        $this->data['referenceCharge'] = $chargeId;
        $this->data['chargeType'] = ChargeType::USAGE();
        $this->data['chargeStatus'] = $chargeStatus;
        $this->data['billingOn'] = $billingOn;
        $this->data['price'] = $price;
        $this->data['description'] = $description;
    }
}
