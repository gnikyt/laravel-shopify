<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Exception;
use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
use OhMyBrew\ShopifyApp\Interfaces\IChargeQuery;
use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;

/**
 * Authenticates a shop via HTTP request.
 */
class ActivatePlanForShop
{
    /**
     * Command for charges.
     *
     * @var IChargeCommand
     */
    protected $chargeCommand;

    /**
     * Querier for charges.
     *
     * @var IChargeQuery
     */
    protected $chargeQuery;

    /**
     * Setup.
     *
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param IChargeQuery   $chargeQuery   The querier for charges.
     *
     * @return self
     */
    public function __construct(IChargeCommand $chargeCommand, IChargeQuery $chargeQuery)
    {
        $this->chargeCommand = $chargeCommand;
        $this->chargeQuery = $chargeQuery;
    }

    /**
     * Execution.
     *
     * @param Plan       $plan     The plan to use.
     * @param string     $chargeId The charge ID from Shopify.
     * @param IShopModel $shop     The shop to charge for the plan.
     *
     * @return object|Exception
     */
    public function __invoke(Plan $plan, string $chargeId, IShopModel $shop)
    {
        // Activate and return the API response
        $response = $shop->api()->rest(
            'POST',
            "/admin/{$plan->typeAsString(true)}/{$chargeId}/activate.json"
        )->body->{$plan->typeAsString()};
        if (!$response) {
            throw new Exception('No activation response was recieved.');
        }

        // Cancel the last charge
        $planCharge = $shop->planCharge();
        if ($planCharge && !$planCharge->isDeclined() && !$planCharge->isCancelled()) {
            $planCharge->cancel();
        }

        // Delete existing charge if it exists
        $exists = $this->chargeQuery->getByShopIdAndChargeId($shop->id, $chargeId);
        if ($exists) {
            $this->chargeCommand->deleteCharge($shop->id, $chargeId);
        }

        // Get the plan's details
        $planDetails = $plan->chargeDetails($shop);
        unset($planDetails['return_url']);

        // Create the charge
        $charge = $this->chargeCommand->createCharge(
            $shop->id,
            $plan->id,
            $chargeId,
            $plan->type,
            $response->status,
            [
                'activated_on'  => $response->activated_on ?? Carbon::today()->format('Y-m-d'),
                'billing_on'    => $plan->isType(Plan::PLAN_RECURRING) ? $response->billing_on : null,
                'trial_ends_on' => $plan->isType(Plan::PLAN_RECURRING) ? $response->trial_ends_on : null,
            ],
            $planDetails
        );

        if ($save) {
            // All good, update the shop's plan and take them off freemium (if applicable)
            $this->shop->update([
                'freemium' => false,
                'plan_id'  => $this->plan->id,
            ]);
        }
    }
}
