<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as PlanQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as ShopCommand;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as ChargeCommand;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
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
     * @var ApiHelper
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
     * @var ShopQuery
     */
    protected $shopQuery;

    /**
     * Command for charges.
     *
     * @var ChargeCommand
     */
    protected $chargeCommand;

    /**
     * Querier for charges.
     *
     * @var ChargeQuery
     */
    protected $chargeQuery;

    /**
     * Command for shops.
     *
     * @var ShopCommand
     */
    protected $shopCommand;

    /**
     * Querier for plans.
     *
     * @var PlanQuery
     */
    protected $planQuery;

    /**
     * Setup.
     *
     * @param ApiHelper     $apiHelper               The API helper.
     * @param callable       $cancelCurrentPlanAction Action which cancels the current plan.
     * @param ChargeCommand $chargeCommand           The commands for charges.
     * @param ShopQuery     $shopQuery               The querier for shops.
     * @param ChargeQuery   $chargeQuery             The querier for charges.
     * @param PlanQuery     $planQuery               The querier for plans.
     * @param ShopCommand   $shopCommand             The commands for shops.
     *
     * @return self
     */
    public function __construct(
        ApiHelper $apiHelper,
        callable $cancelCurrentPlanAction,
        ShopQuery $shopQuery,
        ChargeQuery $chargeQuery,
        PlanQuery $planQuery,
        ChargeCommand $chargeCommand,
        ShopCommand $shopCommand
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
