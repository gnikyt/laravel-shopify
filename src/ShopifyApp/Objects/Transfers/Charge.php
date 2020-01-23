<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Transfers\AbstractTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails;

/**
 * Reprecents create charge.
 */
class ChargeDTO extends AbstractTransfer
{
    /**
     * Constructor.
     *
     * @param int            $shopId       Shop ID.
     * @param int            $planId       Plan ID.
     * @param int            $chargeId     Charge ID from Shopify.
     * @param string         $chargeType   Charge type (recurring or single).
     * @param string         $chargeStatus Charge status.
     * @param Carbon|null    $activatedOn  When the charge was activated.
     * @param Carbon|null    $billingOn    When the charge will be billed on.
     * @param Carbon|null    $trialEndsOn  When the trial ends on.
     * @param PlanDetailsDTO $planDetails  Plan details for reference.
     *
     * @return self
     */
    public function __construct(
        int $shopId,
        int $planId,
        int $chargeId,
        string $chargeType,
        string $chargeStatus,
        ?Carbon $activatedOn,
        ?Carbon $billingOn,
        ?Carbon $trialEndsOn,
        PlanDetailsDTO $planDetails
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
