<?php

namespace OhMyBrew\ShopifyApp\Models;

use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;

/**
 * Responsible for reprecenting a charge record.
 */
class Charge extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'shop_id',
        'charge_id',
        'plan_id',
        'status',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'type'          => 'int',
        'test'          => 'bool',
        'charge_id'     => 'string',
        'shop_id'       => 'int',
        'capped_amount' => 'float',
        'price'         => 'float',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * Gets the shop for the charge.
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Config::get('auth.providers.users.model'));
    }

    /**
     * Gets the plan.
     *
     * @return BelongsTo
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Gets the charge's data from Shopify.
     *
     * @param ApiHelper $apiHelper The API helper.
     *
     * @return object
     */
    public function retrieve(ApiHelper $apiHelper): object
    {
        $path = '';
        switch ($this->type) {
            case ChargeType::CREDIT()->toNative():
                $path = 'application_credits';
                break;
            case ChargeType::ONETIME()->toNative():
                $path = 'application_charges';
                break;
            default:
                $path = 'recurring_application_charges';
                break;
        }

        return $apiHelper->getCharge($path, $this->chargeId);
    }

    /**
     * Checks if the charge is a test.
     *
     * @return bool
     */
    public function isTest(): bool
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
    public function isType(int $type): bool
    {
        return (int) $this->type === $type;
    }

    /**
     * Checks if the charge is a trial-type charge.
     *
     * @return bool
     */
    public function isTrial(): bool
    {
        return !is_null($this->trial_ends_on);
    }

    /**
     * Checks if the charge is currently in trial.
     *
     * @return bool
     */
    public function isActiveTrial(): bool
    {
        return $this->isTrial() &&
            Carbon::today()->lte(Carbon::parse($this->trial_ends_on));
    }

    /**
     * Returns the remaining trial days.
     *
     * @return ?int
     */
    public function remainingTrialDays(): ?int
    {
        if (!$this->isTrial()) {
            return null;
        }

        return $this->isActiveTrial() ?
            Carbon::today()->diffInDays($this->trial_ends_on) :
            0;
    }

    /**
     * Returns the remaining trial days from cancellation date.
     *
     * @return int|null
     */
    public function remainingTrialDaysFromCancel(): ?int
    {
        if (!$this->isTrial()) {
            return null;
        }

        $cancelledDate = Carbon::parse($this->cancelled_on);
        $trialEndsDate = Carbon::parse($this->trial_ends_on);

        // Ensure cancelled date happened before the trial was supposed to end
        if ($this->isCancelled() && $cancelledDate->lte($trialEndsDate)) {
            // Diffeence the two dates and subtract from the total trial days to get whats remaining
            return $this->trial_days - ($this->trial_days - $cancelledDate->diffInDays($trialEndsDate));
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
        $pastPeriods = (int) (Carbon::parse($this->activated_on)->diffInDays(Carbon::today()) / 30);
        $periodBeginDate = Carbon::parse($this->activated_on)->addDays(30 * $pastPeriods)->toDateString();

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

        if ($pastDaysForPeriod == 0 && Carbon::parse($this->cancelled_on)->lt(Carbon::today())) {
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
        if (
            $this->cancelled_on &&
            abs(Carbon::now()->diffInDays(Carbon::parse($this->cancelled_on))) > 30
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
        if ($this->isCancelled()) {
            return Carbon::parse($this->expires_on)->lte(Carbon::today());
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
        if (!$this->isTrial()) {
            return null;
        }

        return $this->trial_days - $this->remainingTrialDays();
    }

    /**
     * Checks the status of the charge.
     *
     * @param string $status The status to check.
     *
     * @return bool
     */
    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    /**
     * Checks if the charge is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isStatus(ChargeStatus::ACTIVE()->toNative());
    }

    /**
     * Checks if the charge was accepted (for one-time and reccuring).
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->isStatus(ChargeStatus::ACCEPTED()->toNative());
    }

    /**
     * Checks if the charge was declined (for one-time and reccuring).
     *
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->isStatus(ChargeStatus::DECLINED()->toNative());
    }

    /**
     * Checks if the charge was cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return !is_null($this->cancelled_on) || $this->isStatus(ChargeStatus::CANCELLED()->toNative());
    }

    /**
     * Checks if the charge is "active" (non-API check).
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        return $this->isActive() && !$this->isCancelled();
    }

    /**
     * Cancels this charge.
     * TODO: Move to command.
     *
     * @throws Exception
     *
     * @return bool
     */
    public function cancel(): bool
    {
        if (!$this->isType(ChargeType::ONETIME()->toNative()) && !$this->isType(ChargeType::RECURRING()->toNative())) {
            throw new Exception('Cancel may only be called for single and recurring charges.');
        }

        $this->status = ChargeStatus::CANCELLED()->toNative();
        $this->cancelled_on = Carbon::today()->format('Y-m-d');
        $this->expires_on = Carbon::today()->addDays($this->remainingDaysForPeriod())->format('Y-m-d');

        return $this->save();
    }
}
