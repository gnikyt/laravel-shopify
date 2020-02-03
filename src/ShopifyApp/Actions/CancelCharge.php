<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Exception;
use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Services\ChargeHelper;

/**
 * Cancels a charge for a shop.
 */
class CancelCharge
{
    /**
     * The commands for charges.
     *
     * @var IChargeCommand
     */
    protected $chargeCommand;

    /**
     * The charge helper.
     *
     * @var ChargeHelper
     */
    protected $chargeHelper;

    /**
     * Constructor.
     *
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param ChargeHelper   $chargeHelper  The charge helper.
     *
     * @return self
     */
    public function __construct(
        IChargeCommand $chargeCommand,
        ChargeHelper $chargeHelper
    ) {
        $this->chargeCommand = $chargeCommand;
        $this->chargeHelper = $chargeHelper;
    }

    /**
     * Cancels the charge.
     *
     * @param ChargeId $chargeId The charge ID.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function __invoke(ChargeId $chargeId): bool
    {
        // Get the charge
        $helper = $this->chargeHelper->useCharge($chargeId);
        $charge = $helper->getCharge();

        if (
            !$charge->isType(ChargeType::ONETIME()->toNative()) &&
            !$charge->isType(ChargeType::RECURRING()->toNative())
        ) {
            throw new Exception('Cancel may only be called for single and recurring charges.');
        }

        // Save the details to the database
        return $this->chargeCommand->cancelCharge(
            $chargeId,
            Carbon::today()->format('Y-m-d'),
            Carbon::today()->addDays($helper->remainingDaysForPeriod())->format('Y-m-d')
        );
    }
}
