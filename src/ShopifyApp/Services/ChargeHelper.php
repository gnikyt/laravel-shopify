<?php

namespace OhMyBrew\ShopifyApp\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use OhMyBrew\ShopifyApp\Storage\Models\Plan;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Basic helper class for charges which encapsulates
 * logic for the charge model for things such as trial
 * determination, charge retrieval, etc.
 */
class ChargeHelper
{
    use ConfigAccessible;

    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

    /**
     * The querier for charges.
     *
     * @var IChargeQuery
     */
    protected $chargeQuery;

    /**
     * The charge record.
     *
     * @var ChargeModel
     */
    protected $charge;

    /**
     * Contructor.
     *
     * @param IApiHelper   $apiHelper   The API helper.
     * @param IChargeQuery $chargeQuery The querier for charges.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IChargeQuery $chargeQuery)
    {
        $this->apiHelper = $apiHelper;
        $this->chargeQuery = $chargeQuery;
    }

    /**
     * Set the charge in context.
     *
     * @param ChargeId $chargeId The charge ID.
     *
     * @return self
     */
    public function useCharge(ChargeId $chargeId): self
    {
        // Get the charge
        $this->charge = $this->chargeQuery->getById($chargeId);

        return $this;
    }

    /**
     * Get the charge in context.
     *
     * @return ChargeModel
     */
    public function getCharge(): ChargeModel
    {
        return $this->charge;
    }

    /**
     * Gets the charge's data from Shopify.
     *
     * @return object
     */
    public function retrieve(): object
    {
        return $this->apiHelper->getCharge($this->charge->getType(), $this->charge->id);
    }

    /**
     * Checks if the charge is currently in trial.
     *
     * @return bool
     */
    public function isActiveTrial(): bool
    {
        return $this->charge->isTrial() &&
            Carbon::today()->lte(Carbon::parse($this->charge->trial_ends_on));
    }

    /**
     * Returns the remaining trial days.
     *
     * @return ?int
     */
    public function remainingTrialDays(): ?int
    {
        if (!$this->charge->isTrial()) {
            return null;
        }

        return $this->isActiveTrial() ?
            Carbon::today()->diffInDays($this->charge->trial_ends_on) :
            0;
    }

    /**
     * Returns the remaining trial days from cancellation date.
     *
     * @return int|null
     */
    public function remainingTrialDaysFromCancel(): ?int
    {
        if (!$this->charge->isTrial()) {
            return null;
        }

        $cancelledDate = Carbon::parse($this->cancelled_on);
        $trialEndsDate = Carbon::parse($this->trial_ends_on);

        // Ensure cancelled date happened before the trial was supposed to end
        if ($this->charge->isCancelled() && $cancelledDate->lte($trialEndsDate)) {
            // Diffeence the two dates and subtract from the total trial days to get whats remaining
            return $this->charge->trial_days - ($this->charge->trial_days - $cancelledDate->diffInDays($trialEndsDate));
        }

        return 0;
    }

    /**
     * return the date when the current period has begun.
     *
     * @return string
     */
    public function periodBeginDate(): string
    {
        $pastPeriods = (int) (Carbon::parse($this->charge->activated_on)->diffInDays(Carbon::today()) / 30);
        $periodBeginDate = Carbon::parse($this->charge->activated_on)->addDays(30 * $pastPeriods)->toDateString();

        return $periodBeginDate;
    }

    /**
     * return the end date of the current period.
     *
     * @return string
     */
    public function periodEndDate(): string
    {
        return Carbon::parse($this->periodBeginDate())->addDays(30)->toDateString();
    }

    /**
     * Returns the remaining days for the current recurring charge.
     *
     * @return int
     */
    public function remainingDaysForPeriod(): int
    {
        $pastDaysForPeriod = $this->pastDaysForPeriod();
        if (is_null($pastDaysForPeriod)) {
            return 0;
        }

        if ($pastDaysForPeriod == 0 && Carbon::parse($this->charge->cancelled_on)->lt(Carbon::today())) {
            return 0;
        }

        return 30 - $pastDaysForPeriod;
    }

    /**
     * Returns the past days for the current recurring charge.
     *
     * @return int|null
     */
    public function pastDaysForPeriod(): ?int
    {
        if ($this->charge->cancelled_on &&
            abs(Carbon::now()->diffInDays(Carbon::parse($this->charge->cancelled_on))) > 30
        ) {
            return null;
        }

        $pastDaysInPeriod = Carbon::parse($this->periodBeginDate())->diffInDays(Carbon::today());

        return $pastDaysInPeriod;
    }

    /**
     * Checks if plan was cancelled and is expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        if ($this->charge->isCancelled()) {
            return Carbon::parse($this->charge->expires_on)->lte(Carbon::today());
        }

        return false;
    }

    /**
     * Returns the used trial days.
     *
     * @return int|null
     */
    public function usedTrialDays(): ?int
    {
        if (!$this->charge->isTrial()) {
            return null;
        }

        return $this->charge->trial_days - $this->remainingTrialDays();
    }

    /**
     * Gets the last single or recurring charge for the shop.
     *
     * @param PlanId     $planId The plan ID to check with.
     * @param IShopModel $shop   The shop the plan is for.
     *
     * @return ChargeModel
     */
    public function chargeForPlan(PlanId $planId, IShopModel $shop): ?ChargeModel
    {
        return $shop
            ->charges()
            ->withTrashed()
            ->whereIn('type', [ChargeType::RECURRING()->toNative(), ChargeType::CHARGE()->toNative()])
            ->where('plan_id', $planId->toNative())
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Determines the trial days for the plan.
     * Detects if reinstall is happening and properly adjusts.
     *
     * @param Plan       $plan The plan.
     * @param IShopModel $shop The shop the plan is for.
     *
     * @return int
     */
    protected function determineTrialDaysRemaining(Plan $plan, IShopModel $shop): int
    {
        if (!$plan->hasTrial()) {
            // Not a trial-type plan, return none
            return 0;
        }

        // See if the shop has been charged for this plan before..
        // If they have, its a good chance its a reinstall
        $pc = $this->chargeForPlan($plan->getId(), $shop);
        if ($pc !== null) {
            return $pc->remainingTrialDaysFromCancel();
        }

        // Seems like a fresh trial... return the days set in database
        return $plan->trial_days;
    }

    /**
     * Returns the charge params used with the create request.
     *
     * @param Plan       $plan The plan.
     * @param IShopModel $shop The shop the plan is for.
     *
     * @return PlanDetailsTransfer
     */
    public function details(Plan $plan, IShopModel $shop): PlanDetailsTransfer
    {
        // Handle capped amounts for UsageCharge API
        $isCapped = isset($plan->capped_amount) && $plan->capped_amount > 0;

        // Build the details object
        return new PlanDetailsTransfer(
            $plan->name,
            $plan->price,
            $plan->isTest(),
            $this->determineTrialDaysRemaining($plan, $shop),
            $isCapped ? $this->capped_amount : null,
            $isCapped ? $this->terms : null,
            URL::secure(
                $this->getConfig('billing_redirect'),
                ['plan_id' => $plan->getId()->toNative()]
            )
        );
    }
}
