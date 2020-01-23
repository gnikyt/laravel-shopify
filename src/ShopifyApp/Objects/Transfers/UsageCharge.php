<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Transfers\AbstractTransfer;
use OhMyBrew\ShopifyApp\Models\Charge;

/**
 * Reprecents create usage charge.
 */
class UsageCharge extends AbstractTransfer
{
    /**
     * Constructor.
     *
     * @param int       $shopId       Shop ID.
     * @param int       $planId       Plan ID.
     * @param int       $chargeId     Charge ID from Shopify.
     * @param string    $chargeStatus Usage charge status.
     * @param float     $price        Usage charge price.
     * @param string    $description  Usage charge description.
     * @param Carbon    $billingOn    When the charge will be billed on.
     *
     * @return self
     */
    public function __construct(
        int $shopId,
        int $planId,
        int $chargeId,
        string $chargeStatus,
        float $price,
        string $description,
        Carbon $billingOn
    ) {
        $this->data['shopId'] = $shopId;
        $this->data['planId'] = $planId;
        $this->data['referenceCharge'] = $chargeId;
        $this->data['chargeType'] = Charge::CHARGE_USAGE;
        $this->data['chargeStatus'] = $chargeStatus;
        $this->data['billingOn'] = $billingOn;
        $this->data['price'] = $price;
        $this->data['description'] = $description;
    }
}
