<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\DTO\UsageChargeDTO;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\DTO\UsageChargeDetailsDTO;
use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;
use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringException;

/**
 * Activates a usage charge for a shop.
 */
class ActivateUsageChargeAction
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
     *
     * @param string $shopDomain  The shop's domain.
     * @param float  $price       Usage charge price.
     * @param string $description Usage charge description.
     *
     * @return int|ChargeNotRecurringException|Exception
     */
    public function __invoke(string $shopDomain, float $price, string $description): int
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));

        // Ensure we have a recurring charge
        $currentCharge = $shop->planCharge();
        if (!$currentCharge->isType(Charge::CHARGE_RECURRING)) {
            throw new ChargeNotRecurringException('Can only create usage charges for recurring charge.');
        }

        // Create the usage charge details
        $ucDetails = new UsageChargeDetailsDTO(
            $currentCharge->charge_id,
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
            new UsageChargeDTO(
                $shop->id,
                $shop->plan->id,
                $currentCharge->charge_id,
                Charge::CHARGE_USAGE,
                $response->status,
                $ucDetails->price,
                $ucDetails->description,
                $response->billing_on,
            )
        );
    }
}
