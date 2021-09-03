<?php

namespace Osiset\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use Osiset\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Exceptions\ChargeNotRecurringException;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Services\ChargeHelper;

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
     * @return void
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
     * @param ShopId                    $shopId The shop ID.
     * @param UsageChargeDetailsTransfer $ucd    The usage charge details (without charge ID).
     *
     * @throws ChargeNotRecurringException
     *
     * @return ChargeId|bool
     */
    public function __invoke(ShopId $shopId, UsageChargeDetailsTransfer $ucd)
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Ensure we have a recurring charge
        $currentCharge = $this->chargeHelper->chargeForPlan($shop->plan->getId(), $shop);
        if (! $currentCharge->isType(ChargeType::RECURRING())) {
            throw new ChargeNotRecurringException('Can only create usage charges for recurring charge.');
        }

        // Create the usage charge
        $ucd->chargeReference = $currentCharge->getReference();
        $response = $shop->apiHelper()->createUsageCharge($ucd);
        if (! $response) {
            // Could not make usage charge, limit possibly reached
            return false;
        }

        // Create the transfer
        $uct = new UsageChargeTransfer();
        $uct->shopId = $shopId;
        $uct->planId = $shop->plan->getId();
        $uct->chargeReference = ChargeReference::fromNative((int) $response['id']);
        $uct->billingOn = new Carbon($response['billing_on']);
        $uct->details = $ucd;

        // Save the usage charge
        return $this->chargeCommand->makeUsage($uct);
    }
}
