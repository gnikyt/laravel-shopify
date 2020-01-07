<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use OhMyBrew\ShopifyApp\DTO\PlanDetailsDTO;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;

/**
 * Responsible for reprecenting a plan record.
 */
class Plan extends Model
{
    // Types of plans
    const PLAN_RECURRING = 1;
    const PLAN_ONETIME = 2;

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'type'          => 'int',
        'test'          => 'bool',
        'on_install'    => 'bool',
        'capped_amount' => 'float',
        'price'         => 'float',
    ];

    /**
     * Get charges.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function charges()
    {
        return $this->hasMany(Charge::class);
    }

    /**
     * Checks the plan type.
     *
     * @param int $type The plan type.
     *
     * @return bool
     */
    public function isType(int $type)
    {
        return $this->type === $type;
    }

    /**
     * Returns the plan type as a string (for API).
     *
     * @param bool $plural Return the plural form or not.
     *
     * @return string
     */
    public function typeAsString($plural = false)
    {
        $type = null;
        switch ($this->type) {
            case self::PLAN_ONETIME:
                $type = 'application_charge';
                break;
            default:
            case self::PLAN_RECURRING:
                $type = 'recurring_application_charge';
                break;
        }

        return $plural ? "{$type}s" : $type;
    }

    /**
     * Checks if this plan has a trial.
     *
     * @return bool
     */
    public function hasTrial()
    {
        return $this->trial_days !== null && $this->trial_days > 0;
    }

    /**
     * Checks if this plan should be presented on install.
     *
     * @return bool
     */
    public function isOnInstall()
    {
        return (bool) $this->on_install;
    }

    /**
     * Checks if the plan is a test.
     *
     * @return bool
     */
    public function isTest()
    {
        return (bool) $this->test;
    }

    /**
     * Returns the charge params sent with the post request.
     *
     * @param IShopModel $shop The shop the plan is for.
     *
     * @return PlanDetailsDTO
     */
    public function chargeDetails(IShopModel $shop): PlanDetailsDTO
    {
        // Handle capped amounts for UsageCharge API
        $isCapped = isset($this->capped_amount) && $this->capped_amount > 0;

        // Build the details object
        return new PlanDetailsDTO(
            $this->name,
            $this->price,
            $this->isTest(),
            $this->determineTrialDaysForShop($shop),
            $isCapped ? $this->capped_amount : null,
            $isCapped ? $this->terms : null,
            URL::secure(
                Config::get('shopify-app.billing_redirect'),
                ['plan_id' => $this->id]
            )
        );
    }

    /**
     * Determines the trial days for the plan.
     * Detects if reinstall is happening and properly adjusts.
     * 
     * @param IShopModel $shop The shop the plan is for.
     *
     * @return int
     */
    public function determineTrialDaysForShop(IShopModel $shop): int
    {
        if (!$this->hasTrial()) {
            // Not a trial-type plan, return none
            return 0;
        }

        // See if the shop has been charged for this plan before..
        // If they have, its a good chance its a reinstall
        $pc = $shop->planCharge($this->plan->id);
        if ($pc !== null) {
            return $pc->remainingTrialDaysFromCancel();
        }

        // Seems like a fresh trial... return the days set in database
        return $this->trial_days;
    }

    /**
     * API helper for plan activation.
     *
     * @param IShopModel $shop     The shop object.
     * @param int        $chargeId The charge ID from Shopify to use for the call.
     *
     * @return object
     */
    public function apiChargeActivate(IShopModel $shop, int $chargeId): object
    {
        return $shop
            ->api()
            ->rest(
                'POST',
                "/admin/{$this->typeAsString(true)}/{$chargeId}/activate.json"
            )
            ->body
            ->{$this->typeAsString()};
    }

    /**
     * API helper for charge creation.
     *
     * @param IShopModel $shop     The shop object.
     *
     * @return object
     */
    public function apiCreateCharge(IShopModel $shop): object
    {
        return $shop
            ->api()
            ->rest(
                'POST',
                "/admin/{$this->typeAsString(true)}.json",
                [
                    "{$this->typeAsString()}" => $this->chargeDetails($this),
                ]
            )
            ->body
            ->{$this->typeAsString()};
    }
}
