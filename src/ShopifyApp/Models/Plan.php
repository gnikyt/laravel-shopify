<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Responsible for reprecenting a plan record.
 *
 * @property int $id
 * @property int $type
 * @property string $name
 * @property float $price
 * @property float $capped_amount
 * @property string $terms
 * @property int $trial_days
 * @property bool $test
 * @property bool $on_install
 * @property bool $additional
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @mixin Builder
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
        'additional'    => 'bool',
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
     * Checks if this plan should not override current shop's plan
     *
     * @return bool
     */
    public function isAdditional()
    {
        return (bool) $this->additional && $this->isType(self::PLAN_ONETIME);
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
}
