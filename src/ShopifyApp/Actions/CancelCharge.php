<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Services\ChargeHelper;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringOrOnetimeException;

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
     * @param ChargeReference $chargeRef The charge ID.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function __invoke(ChargeReference $chargeRef): bool
    {
        // Get the charge
        $helper = $this->chargeHelper->useCharge($chargeRef);
        $charge = $helper->getCharge();

        if (!$charge->isType(ChargeType::CHARGE()) && !$charge->isType(ChargeType::RECURRING())) {
            // Not a recurring or one-time charge, someone trying to cancel a usage charge?
            throw new ChargeNotRecurringOrOnetimeException(
                'Cancel may only be called for single and recurring charges.'
            );
        }

        // Save the details to the database
        return $this->chargeCommand->cancel(
            $chargeRef,
            Carbon::today(),
            Carbon::today()->addDays($helper->remainingDaysForPeriod())
        );
    }
}
