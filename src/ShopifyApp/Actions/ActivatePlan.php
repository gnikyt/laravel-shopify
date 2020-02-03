<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Activates a plan for a shop.
 */
class ActivatePlan
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
     * @var callable
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
     * @param IApiHelper     $apiHelper               The API helper.
     * @param callable       $cancelCurrentPlanAction Action which cancels the current plan.
     * @param IChargeCommand $chargeCommand           The commands for charges.
     * @param IShopQuery     $shopQuery               The querier for shops.
     * @param IChargeQuery   $chargeQuery             The querier for charges.
     * @param IPlanQuery     $planQuery               The querier for plans.
     * @param IShopCommand   $shopCommand             The commands for shops.
     *
     * @return self
     */
    public function __construct(
        IApiHelper $apiHelper,
        callable $cancelCurrentPlanAction,
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
     * TODO: Rethrow an API exception.
     *
     * @param ShopId   $shopId   The shop ID.
     * @param PlanId   $planId   The plan to use.
     * @param ChargeId $chargeId The charge ID from Shopify.
     *
     * @return bool
     */
    public function __invoke(ShopId $shopId, PlanId $planId, ChargeId $chargeId): bool
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

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
        $planRecurring = PlanType::RECURRING()->toNative();
        $charge = $this->chargeCommand->createCharge(
            new ChargeTransfer(
                $shop->id,
                $plan->id,
                $chargeId,
                $plan->type,
                $response->status,
                $response->activated_on ?? Carbon::today()->format('Y-m-d'),
                $plan->isType($planRecurring) ? $response->billing_on : null,
                $plan->isType($planRecurring) ? $response->trial_ends_on : null,
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
