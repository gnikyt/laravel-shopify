<?php

namespace OhMyBrew\ShopifyApp\Storage\Models;

use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
     * Returns the charge type as a string (for API).
     *
     * @param bool $plural Return the plural form or not.
     *
     * @return string
     */
    public function typeAsString($plural = false): string
    {
        $type = '';
        switch ($this->type) {
            case ChargeType::CREDIT()->toNative():
                $type = 'application_credit';
                break;
            case ChargeType::ONETIME()->toNative():
                $type = 'application_charge';
                break;
            default:
                $type = 'recurring_application_charge';
                break;
        }

        return $plural ? "{$type}s" : $type;
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
}
