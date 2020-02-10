<?php

namespace OhMyBrew\ShopifyApp\Storage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OhMyBrew\ShopifyApp\Objects\Enums\PlanType;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;

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
     * Get the plan ID as a value object.
     *
     * @return PlanId
     */
    public function getId(): PlanId
    {
        return new PlanId($this->id);
    }

    /**
     * Gets the type of plan.
     *
     * @return PlanType
     */
    public function getType(): PlanType
    {
        return PlanType::fromNative($this->type);
    }

    /**
     * Checks the plan type.
     *
     * @param PlamType $type The plan type.
     *
     * @return bool
     */
    public function isType(PlanType $type): bool
    {
        return $this->getType()->isSame($type);
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
}
