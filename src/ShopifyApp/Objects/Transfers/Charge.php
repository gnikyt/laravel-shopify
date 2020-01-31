<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Transfers\AbstractTransfer;

/**
 * Reprecents create charge.
 */
final class Charge extends AbstractTransfer
{
    /**
     * Constructor.
     *
     * @param ShopId      $shopId       Shop ID.
     * @param PlanId      $planId       Plan ID.
     * @param ChargeId    $chargeId     Charge ID from Shopify.
     * @param string      $chargeType   Charge type (recurring or single).
     * @param string      $chargeStatus Charge status.
     * @param Carbon|null $activatedOn  When the charge was activated.
     * @param Carbon|null $billingOn    When the charge will be billed on.
     * @param Carbon|null $trialEndsOn  When the trial ends on.
     * @param PlanDetails $planDetails  Plan details for reference.
     *
     * @return self
     */
    public function __construct(
        ShopId $shopId,
        PlanId $planId,
        ChargeId $chargeId,
        string $chargeType,
        string $chargeStatus,
        ?Carbon $activatedOn,
        ?Carbon $billingOn,
        ?Carbon $trialEndsOn,
        PlanDetails $planDetails
    ) {
        $this->data['shopId'] = $shopId;
        $this->data['planId'] = $planId;
        $this->data['chargeId'] = $chargeId;
        $this->data['chargeType'] = $chargeType;
        $this->data['chargeStatus'] = $chargeStatus;
        $this->data['activatedOn'] = $activatedOn;
        $this->data['billingOn'] = $billingOn;
        $this->data['trialEndsOn'] = $trialEndsOn;
        $this->data['planDetails'] = $planDetails;
    }
}
