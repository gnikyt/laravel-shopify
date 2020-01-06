<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\DTO\ChargeDTO;
use OhMyBrew\ShopifyApp\DTO\DeleteChargeDTO;
use OhMyBrew\ShopifyApp\DTO\PlanDetailsDTO;
use OhMyBrew\ShopifyApp\DTO\ShopSetPlanDTO;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
use OhMyBrew\ShopifyApp\Interfaces\IChargeQuery;
use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;
use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;
use OhMyBrew\ShopifyApp\Exceptions\ChargeActivationException;
use OhMyBrew\ShopifyApp\Interfaces\IPlanQuery;

/**
 * Activates a plan for a shop.
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
     * Querier for plans.
     *
     * @var IPlanQuery
     */
    protected $planQuery;

    /**
     * Setup.
     *
     * @param IChargeCommand $chargeCommand The commands for charges.
     * @param IChargeQuery   $chargeQuery   The querier for charges.
     * @param IShopCommand   $shopCommand   The commands for shops.
     * @param IPlanQuery     $planQuery     The querier for plans.
     *
     * @return self
     */
    public function __construct(
        IChargeCommand $chargeCommand,
        IChargeQuery $chargeQuery,
        IShopCommand $shopCommand,
        IPlanQuery $planQuery
    ) {
        $this->chargeCommand = $chargeCommand;
        $this->chargeQuery = $chargeQuery;
        $this->shopCommand = $shopCommand;
        $this->planQuery = $planQuery;
    }

    /**
     * Execution.
     *
     * @param IShopModel $shop     The shop to charge for the plan.
     * @param int        $planId   The plan to use.
     * @param string     $chargeId The charge ID from Shopify.
     *
     * @return bool|Exception
     */
    public function __invoke(IShopModel $shop, int $planId, string $chargeId)
    {
        // Get the plan
        $plan = $this->planQuery->getById($planId);

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
            $this->chargeCommand->deleteCharge($shop->id, $chargeId);
        }

        // Create the charge
        $charge = $this->chargeCommand->createCharge(
            new ChargeDTO(
                $shop->id,
                $plan->id,
                $chargeId,
                $plan->type,
                $response->status,
                $response->activated_on ?? Carbon::today()->format('Y-m-d'),
                $plan->isType(Plan::PLAN_RECURRING) ? $response->billing_on : null,
                $plan->isType(Plan::PLAN_RECURRING) ? $response->trial_ends_on : null,
                $plan->chargeDetails($shop)
            )
        );
        if ($charge) {
            // All good, update the shop's plan and take them off freemium (if applicable)
            return $this->shopCommand->setToPlan($shop->id, $plan->id);
        }

        return false;
    }
}
