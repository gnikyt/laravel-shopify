<?php

namespace OhMyBrew\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use OhMyBrew\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use OhMyBrew\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use OhMyBrew\ShopifyApp\Services\ChargeHelper;

/**
 * Activates a plan for a shop.
 */
class ActivatePlan
{
    /**
     * The charge helper.
     *
     * @var ChargeHelper
     */
    protected $chargeHelper;

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
     * @param callable       $cancelCurrentPlanAction Action which cancels the current plan.
     * @param ChargeHelper   $chargeHelper            The charge helper.
     * @param IShopQuery     $shopQuery               The querier for shops.
     * @param IChargeQuery   $chargeQuery             The querier for charges.
     * @param IPlanQuery     $planQuery               The querier for plans.
     * @param IChargeCommand $chargeCommand           The commands for charges.
     * @param IShopCommand   $shopCommand             The commands for shops.
     *
     * @return self
     */
    public function __construct(
        callable $cancelCurrentPlanAction,
        ChargeHelper $chargeHelper,
        IShopQuery $shopQuery,
        IChargeQuery $chargeQuery,
        IPlanQuery $planQuery,
        IChargeCommand $chargeCommand,
        IShopCommand $shopCommand
    ) {
        $this->cancelCurrentPlan = $cancelCurrentPlanAction;
        $this->chargeHelper = $chargeHelper;
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
        $chargeType = ChargeType::fromNative($plan->getType()->toNative());
        $response = $shop->apiHelper()->activateCharge($chargeType, $chargeId);

        // Cancel the last charge, delete existing charge (if it exists)
        call_user_func($this->cancelCurrentPlan, $shop->getId());
        $exists = $this->chargeQuery->getByShopIdAndChargeId($shop->getId(), $chargeId);
        if ($exists) {
            $this->chargeCommand->deleteCharge($shop->getId(), $chargeId);
        }

        // Create the charge
        $isRecurring = $plan->isType(PlanType::RECURRING());
        $charge = $this->chargeCommand->createCharge(
            new ChargeTransfer(
                $shop->getId(),
                $plan->getId(),
                $chargeId,
                $chargeType,
                ChargeStatus::fromNative(strtoupper($response->status)),
                $response->activated_on ? new Carbon($response->activated_on) : Carbon::today(),
                $isRecurring ? new Carbon($response->billing_on) : null,
                $isRecurring ? new Carbon($response->trial_ends_on) : null,
                $this->chargeHelper->details($plan, $shop)
            )
        );
        if ($charge) {
            // All good, update the shop's plan and take them off freemium (if applicable)
            return $this->shopCommand->setToPlan($shop->getId(), $plan->getId());
        }

        return false;
    }
}
