<?php

namespace OhMyBrew\ShopifyApp\DTO;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\DTO\AbstractDTO;

/**
 * Reprecents create charge.
 */
class ChargeDTO extends AbstractDTO
{
    /**
     * Shop ID.
     *
     * @var int
     */
    private $shopId;

    /**
     * Plan ID.
     *
     * @var int
     */
    private $planId;

    /**
     * Charge ID from Shopify.
     *
     * @var int
     */
    private $chargeId;

    /**
     * Charge type (recurring or single).
     *
     * @var string
     */
    private $chargeType;

    /**
     * Charge status.
     *
     * @var string
     */
    private $chargeStatus;

    /**
     * When the charge was activated.
     *
     * @var Carbon|null
     */
    private $activatedOn;

    /**
     * When the charge will be billed on.
     *
     * @var Carbon|null
     */
    private $billingOn;

    /**
     * When the trial ends on.
     *
     * @var Carbon|null
     */
    private $trialEndsOn;

    /**
     * Plan details for reference.
     *
     * @var PlanDetailsDTO
     */
    private $planDetails;

    /**
     * Constructor.
     *
     * @param int            $shopId       Shop ID.
     * @param int            $planId       Plan ID.
     * @param int            $chargeId     Charge ID from Shopify.
     * @param string         $chargeType   Charge type (recurring or single).
     * @param string         $chargeStatus Charge status.
     * @param Carbon         $activatedOn  When the charge was activated.
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
        Carbon $activatedOn,
        ?Carbon $billingOn,
        ?Carbon $trialEndsOn,
        PlanDetailsDTO $planDetails
    ) {
        $this->shopId = $shopId;
        $this->planId = $planId;
        $this->chargeType = $chargeType;
        $this->chargeStatus = $chargeStatus;
        $this->activatedOn = $activatedOn;
        $this->billingOn = $billingOn;
        $this->trialEndsOn = $trialEndsOn;
        $this->planDetails = $planDetails;
    }
}
