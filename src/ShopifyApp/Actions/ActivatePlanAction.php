<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\DTO\ChargeDTO;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IPlanQuery;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;
use OhMyBrew\ShopifyApp\Interfaces\IChargeQuery;
use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;
use OhMyBrew\ShopifyApp\Interfaces\IChargeCommand;
use OhMyBrew\ShopifyApp\Actions\CancelCurrentPlanAction;

/**
 * Activates a plan for a shop.
 */
class ActivatePlanAction
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * Action which cancels the current plan.
     *
     * @var CancelCurrentPlan
     */
    protected $cancelCurrentPlan;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

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
     * @param IApiHelper              $apiHelper               The API helper.
     * @param CancelCurrentPlanAction $cancelCurrentPlanAction Action which cancels the current plan.
     * @param IChargeCommand          $chargeCommand           The commands for charges.
     * @param IShopQuery              $shopQuery               The querier for shops.
     * @param IChargeQuery            $chargeQuery             The querier for charges.
     * @param IPlanQuery              $planQuery               The querier for plans.
     * @param IShopCommand            $shopCommand             The commands for shops.
     *
     * @return self
     */
    public function __construct(
        IApiHelper $apiHelper,
        CancelCurrentPlanAction $cancelCurrentPlanAction,
        IShopQuery $shopQuery,
        IChargeQuery $chargeQuery,
        IPlanQuery $planQuery,
        IChargeCommand $chargeCommand,
        IShopCommand $shopCommand
    ) {
        $this->apiHelper = $apiHelper;
        $this->cancelCurrentPlan = $cancelCurrentPlanAction;
        $this->chargeQuery = $chargeQuery;
        $this->shopQuery = $shopQuery;
        $this->planQuery = $planQuery;
        $this->chargeCommand = $chargeCommand;
        $this->shopCommand = $shopCommand;
    }

    /**
     * Execution.
     *
     * @param string     $shopDomain The shop's domain.
     * @param int        $planId     The plan to use.
     * @param int        $chargeId   The charge ID from Shopify.
     *
     * @return bool|Exception
     */
    public function __invoke(string $shopDomain, int $planId, int $chargeId)
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));

        // Get the plan and activate
        $plan = $this->planQuery->getById($planId);
        $response = $this
            ->apiHelper
            ->setInstance($shop->api())
            ->activateCharge($plan->typeAsString(true), $chargeId);

        // Cancel the last charge, delete existing charge (if it exists)
        call_user_func($this->cancelCurrentPlan, $shop);
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
