<?php

namespace OhMyBrew\ShopifyApp\Storage\Models;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use OhMyBrew\ShopifyApp\Contracts\ShopModel;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;

/**
 * Responsible for reprecenting a plan record.
 */
class Plan extends Model
{
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
     * @return HasMany
     */
    public function charges(): HasMany
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
    public function isType(int $type): bool
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
    public function typeAsString($plural = false): string
    {
        $type = null;
        switch ($this->type) {
            case PlanType::ONETIME()->toNative():
                $type = 'application_charge';
                break;
            default:
            case PlanType::RECURRING()->toNative():
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
    public function hasTrial(): bool
    {
        return $this->trial_days !== null && $this->trial_days > 0;
    }

    /**
     * Checks if this plan should be presented on install.
     *
     * @return bool
     */
    public function isOnInstall(): bool
    {
        return (bool) $this->on_install;
    }

    /**
     * Checks if the plan is a test.
     *
     * @return bool
     */
    public function isTest(): bool
    {
        return (bool) $this->test;
    }

    /**
     * Returns the charge params sent with the post request.
     *
     * @param ShopModel $shop The shop the plan is for.
     *
     * @return PlanDetailsTransfer
     */
    public function chargeDetails(ShopModel $shop): PlanDetailsTransfer
    {
        // Handle capped amounts for UsageCharge API
        $isCapped = isset($this->capped_amount) && $this->capped_amount > 0;

        // Build the details object
        return new PlanDetailsTransfer(
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
}
