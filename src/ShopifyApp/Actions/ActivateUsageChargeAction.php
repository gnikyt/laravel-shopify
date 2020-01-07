<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Exceptions\ChargeNotRecurringException;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;
use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;

/**
 * Activates a usage charge for a shop.
 */
class ActivateUsageChargeAction
{
    /**
     * Command for charges.
     *
     * @var IChargeCommand
     */
    protected $chargeCommand;

    /**
     * Command for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * Setup.
     *
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param IShopCommand   $shopCommand   The commands for shops.
     *
     * @return self
     */
    public function __construct(IChargeCommand $chargeCommand, IShopCommand $shopCommand)
    {
        $this->chargeCommand = $chargeCommand;
        $this->shopCommand = $shopCommand;
    }

    /**
     * Execute.
     *
     * @return void
     */
    public function __invoke(IShopModel $shop)
    {
        // Ensure we have a recurring charge
        $currentCharge = $shop->planCharge();
        if (!$currentCharge->isType(Charge::CHARGE_RECURRING)) {
            throw new ChargeNotRecurringException('Can only create usage charges for recurring charge.');
        }

        $response = $shop->api()->rest(
            'POST',
            "/admin/recurring_application_charges/{$currentCharge->charge_id}/usage_charges.json",
            [
                'usage_charge' => [
                    'price'       => $this->data['price'],
                    'description' => $this->data['description'],
                ],
            ]
        )->body->usage_charge;
    }
}
