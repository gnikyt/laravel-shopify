<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Services\ChargeHelper;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringException;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;

/**
 * Activates a usage charge for a shop.
 */
class ActivateUsageCharge
{
    /**
     * The helper for charges.
     *
     * @var ChargeHelper
     */
    protected $chargeHelper;

    /**
     * Command for charges.
     *
     * @var IChargeCommand
     */
    protected $chargeCommand;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param ChargeHelper   $chargeHelper  The helper for charges.
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param IShopQuery     $shopQuery     The querier for shops.
     *
     * @return self
     */
    public function __construct(
        ChargeHelper $chargeHelper,
        IChargeCommand $chargeCommand,
        IShopQuery $shopQuery
    ) {
        $this->chargeHelper = $chargeHelper;
        $this->chargeCommand = $chargeCommand;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execute.
     * TODO: Rethrow an API exception.
     *
     * @param ShopeId                    $shopId The shop ID.
     * @param UsageChargeDetailsTransfer $ucd    The usage charge details (without charge ID).
     *
     * @throws ChargeNotRecurringException
     *
     * @return int
     */
    public function __invoke(ShopId $shopId, UsageChargeDetailsTransfer $ucd): int
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Ensure we have a recurring charge
        $currentCharge = $this->chargeHelper->chargeForPlan($shop->plan->getId(), $shop);
        if (!$currentCharge->isType(ChargeType::RECURRING())) {
            throw new ChargeNotRecurringException('Can only create usage charges for recurring charge.');
        }

        // Create the usage charge
        $ucd->chargeReference = $currentCharge->getReference();
        $response = $shop->apiHelper()->createUsageCharge($ucd);

        // Create the transder
        $uct = new UsageChargeTransfer();
        $uct->shopId = $shopId;
        $uct->planId = $shop->plan->getId();
        $uct->chargeReference = new ChargeReference($response->id);
        $uct->billingOn = new Carbon($response->billing_on);
        $uct->details = $ucd;

        // Save the usage charge
        return $this->chargeCommand->createUsageCharge($uct);
    }
}
