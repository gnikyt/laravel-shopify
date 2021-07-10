<?php

namespace Osiset\ShopifyApp\Actions;

use Illuminate\Support\Carbon;
use Osiset\ShopifyApp\Contracts\Commands\Charge as IChargeCommand;
use Osiset\ShopifyApp\Contracts\Commands\Shop as IShopCommand;
use Osiset\ShopifyApp\Contracts\Objects\Values\PlanId;
use Osiset\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Enums\PlanType;
use Osiset\ShopifyApp\Objects\Transfers\Charge as ChargeTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Services\ChargeHelper;

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
     * @param IPlanQuery     $planQuery               The querier for plans.
     * @param IChargeCommand $chargeCommand           The commands for charges.
     * @param IShopCommand   $shopCommand             The commands for shops.
     *
     * @return void
     */
    public function __construct(
        callable $cancelCurrentPlanAction,
        ChargeHelper $chargeHelper,
        IShopQuery $shopQuery,
        IPlanQuery $planQuery,
        IChargeCommand $chargeCommand,
        IShopCommand $shopCommand
    ) {
        $this->cancelCurrentPlan = $cancelCurrentPlanAction;
        $this->chargeHelper = $chargeHelper;
        $this->shopQuery = $shopQuery;
        $this->planQuery = $planQuery;
        $this->chargeCommand = $chargeCommand;
        $this->shopCommand = $shopCommand;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopId          $shopId    The shop ID.
     * @param PlanId          $planId    The plan to use.
     * @param ChargeReference $chargeRef The charge ID from Shopify.
     *
     * @return ChargeId
     */
    public function __invoke(ShopId $shopId, PlanId $planId, ChargeReference $chargeRef): ChargeId
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // Get the plan
        $plan = $this->planQuery->getById($planId);
        $chargeType = ChargeType::fromNative($plan->getType()->toNative());

        // Activate the plan on Shopify
        $response = $shop->apiHelper()->activateCharge($chargeType, $chargeRef);

        // Cancel the shop's current plan
        call_user_func($this->cancelCurrentPlan, $shopId);

        // Cancel the existing charge if it exists (happens if someone refreshes during)
        $this->chargeCommand->delete($chargeRef, $shopId);

        // Create the charge transfer
        $transfer = new ChargeTransfer();
        $transfer->shopId = $shopId;
        $transfer->planId = $planId;
        $transfer->chargeReference = $chargeRef;
        $transfer->chargeType = $chargeType;
        $transfer->chargeStatus = ChargeStatus::fromNative(strtoupper($response['status']));
        if ($plan->isType(PlanType::RECURRING())) {
            $transfer->activatedOn = new Carbon($response['activated_on']);
            $transfer->billingOn = new Carbon($response['billing_on']);
            $transfer->trialEndsOn = new Carbon($response['trial_ends_on']);
        } else {
            $transfer->activatedOn = Carbon::today();
            $transfer->billingOn = null;
            $transfer->trialEndsOn = null;
        }
        $transfer->planDetails = $this->chargeHelper->details($plan, $shop);

        // Create the charge
        $charge = $this->chargeCommand->make($transfer);
        $this->shopCommand->setToPlan($shopId, $planId);

        return $charge;
    }
}
