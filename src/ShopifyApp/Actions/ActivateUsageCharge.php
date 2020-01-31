<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringException;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as UChargeCommand;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageCharge as UsageChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;

/**
 * Activates a usage charge for a shop.
 */
class ActivateUsageCharge
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

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
     * @param IApiHelper     $apiHelper     The API helper.
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param IShopQuery     $shopQuery     The querier for shops.
     *
     * @return self
     */
    public function __construct(
        IApiHelper $apiHelper,
        IChargeCommand $chargeCommand,
        IShopQuery $shopQuery
    ) {
        $this->apiHelper = $apiHelper;
        $this->chargeCommand = $chargeCommand;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execute.
     * TODO: Rethrow an API exception.
     *
     * @param ShopeId $shopId      The shop ID.
     * @param float   $price       Usage charge price.
     * @param string  $description Usage charge description.
     *
     * @throws ChargeNotRecurringException
     *
     * @return int
     */
    public function __invoke(ShopId $shopId, float $price, string $description): int
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Ensure we have a recurring charge
        $currentCharge = $shop->planCharge();
        if (!$currentCharge->isType(ChargeType::RECURRING()->toNative())) {
            throw new ChargeNotRecurringException('Can only create usage charges for recurring charge.');
        }

        // Create the usage charge details
        $ucDetails = new UsageChargeDetailsTransfer(
            new ChargeId($currentCharge->charge_id),
            $price,
            $description
        );

        // Create the usage charge
        $response = $this
            ->apiHelper
            ->setInstance($shop->api())
            ->createUsageCharge($ucDetails);

        // Save the usage charge
        return $this->chargeCommand->createUsageCharge(
            new UsageChargeTransfer(
                $shopId,
                new PlanId($shop->plan->id),
                new ChargeId($currentCharge->charge_id),
                ChargeType::USAGE()->toNative(),
                $response->status,
                $ucDetails->price,
                $ucDetails->description,
                $response->billing_on,
            )
        );
    }
}
