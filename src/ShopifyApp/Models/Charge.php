<?php

namespace OhMyBrew\ShopifyApp\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Charge extends Model
{
    use SoftDeletes;

    // Types of charges
    const CHARGE_RECURRING = 1;
    const CHARGE_ONETIME = 2;
    const CHARGE_USAGE = 3;
    const CHARGE_CREDIT = 4;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Scope for latest charge for a shop.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Scope for latest charge by type for a shop.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
     * @param int                                   $type  The type of charge
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLatestByType($query, int $type)
    {
        return $query->where('type', $type)->orderBy('created_at', 'desc')->first();
    }

    /**
     * Gets the shop for the charge.
     *
     * @return OhMyBrew\ShopifyApp\Models\Shop
     */
    public function shop()
    {
        return $this->belongsTo('OhMyBrew\ShopifyApp\Models\Shop');
    }

    /**
     * Checks if the charge is a test.
     *
     * @return bool
     */
    public function isTest()
    {
        return (bool) $this->test;
    }

    /**
     * Checks if the charge is a type.
     *
     * @param int $type The charge type.
     *
     * @return bool
     */
    public function isType(int $type)
    {
        return (int) $this->type === $type;
    }

    /**
     * Checks if the charge is a trial-type charge.
     *
     * @return bool
     */
    public function isTrial()
    {
        return !is_null($this->trial_ends_on);
    }

    /**
     * Checks if the charge is currently in trial.
     *
     * @return bool
     */
    public function isActiveTrial()
    {
        return $this->isTrial() && Carbon::today()->lte(Carbon::parse($this->trial_ends_on));
    }

    /**
     * Returns the remaining trial days.
     *
     * @return int
     */
    public function remainingTrialDays()
    {
        if (!$this->isTrial()) {
            return;
        }

        return $this->isActiveTrial() ? Carbon::today()->diffInDays($this->trial_ends_on) : 0;
    }

    /**
     * Returns the used trial days.
     *
     * @return int|null
     */
    public function usedTrialDays()
    {
        if (!$this->isTrial()) {
            return;
        }

        return $this->trial_days - $this->remainingTrialDays();
    }

    /**
     * Checks if the charge is active.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * Checks if the charge was accepted (for one-time and reccuring).
     *
     * @return bool
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Checks if the charge was declined (for one-time and reccuring).
     *
     * @return bool
     */
    public function isDeclined()
    {
        return $this->status === 'declined';
    }

    /**
     * Checks if the charge was cancelled.
     *
     * @return bool
     */
    public function isCancelled()
    {
        return !is_null($this->cancelled_on) || $this->status === 'cancelled';
    }

    /**
     * Checks if the charge is "active" (non-API check).
     *
     * @return boolean
     */
    public function isOngoing()
    {
        return $this->isActive() && !$this->isCancelled();
    }
}
