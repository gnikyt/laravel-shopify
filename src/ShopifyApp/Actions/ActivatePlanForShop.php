<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\DTO\CreateChargeDTO;
use OhMyBrew\ShopifyApp\DTO\DeleteChargeDTO;
use OhMyBrew\ShopifyApp\DTO\PlanDetailsDTO;
use OhMyBrew\ShopifyApp\DTO\ShopSetPlanDTO;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
use OhMyBrew\ShopifyApp\Interfaces\IChargeQuery;
use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;
use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;
use OhMyBrew\ShopifyApp\Exceptions\ChargeActivationException;

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
     * Command for shops.
     *
     * @var IShopCommand
     */
    protected $shopCommand;

    /**
     * Setup.
     *
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param IChargeQuery   $chargeQuery   The querier for charges.
     *
     * @return self
     */
    public function __construct(
        IChargeCommand $chargeCommand,
        IChargeQuery $chargeQuery,
        IShopCommand $shopCommand
    ) {
        $this->chargeCommand = $chargeCommand;
        $this->chargeQuery = $chargeQuery;
        $this->shopCommand = $shopCommand;
    }

    /**
     * Execution.
     *
     * @param Plan       $plan     The plan to use.
     * @param string     $chargeId The charge ID from Shopify.
     * @param IShopModel $shop     The shop to charge for the plan.
     *
     * @return bool|Exception
     */
    public function __invoke(Plan $plan, string $chargeId, IShopModel $shop)
    {
        // Activate and return the API response
        $response = $shop->api()->rest(
            'POST',
            "/admin/{$plan->typeAsString(true)}/{$chargeId}/activate.json"
        )->body->{$plan->typeAsString()};
        if (!$response) {
            throw new ChargeActivationException('No activation response was recieved.');
        }

        // Cancel the last charge
        $planCharge = $shop->planCharge();
        if ($planCharge && !$planCharge->isDeclined() && !$planCharge->isCancelled()) {
            $planCharge->cancel();
        }

        // Delete existing charge if it exists
        $exists = $this->chargeQuery->getByShopIdAndChargeId($shop->id, $chargeId);
        if ($exists) {
            $deleteCharge = new DeleteChargeDTO();
            $deleteCharge->shopId = $shop->id;
            $deleteCharge->chargeId = $chargeId;

            $this->chargeCommand->deleteCharge($deleteCharge);
        }

        // Get the plan's details
        $planDetails = $plan->chargeDetails($shop);
        
        // Create the charge object
        $charge = new CreateChargeDTO();
        $charge->shopId = $shop->id;
        $charge->planId = $plan->id;
        $charge->chargeId = $chargeId;
        $charge->chargeType = $plan->type;
        $charge->chargeStatus = $response->status;
        $charge->activatedOn = $response->activated_on ?? Carbon::today()->format('Y-m-d');
        $charge->billingOn = $plan->isType(Plan::PLAN_RECURRING) ? $response->billing_on : null;
        $charge->trialEndsOn = $plan->isType(Plan::PLAN_RECURRING) ? $response->trial_ends_on : null;
        $charge->planDetails = $planDetails;

        // Create the charge
        $result = $this->chargeCommand->createCharge($charge);
        if ($charge) {
            // All good, update the shop's plan and take them off freemium (if applicable)
            $stp = new ShopSetPlanDTO();
            $stp->shopId = $shop->id;
            $stp->planId = $plan->id;

            return $this->shopCommand->setToPlan($stp);
        }

        return false;
    }
}
