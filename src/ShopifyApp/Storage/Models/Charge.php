<?php

namespace Osiset\ShopifyApp\Storage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use function Osiset\ShopifyApp\getShopifyConfig;
use Osiset\ShopifyApp\Objects\Enums\ChargeStatus;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;

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
        'user_id',
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
        'test'          => 'bool',
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
     * Get the ID as a value object.
     *
     * @return ChargeId
     */
    public function getId(): ChargeId
    {
        return ChargeId::fromNative((int) $this->id);
    }

    /**
     * Get the charge ID as a value object.
     *
     * @return ChargeReference
     */
    public function getReference(): ChargeReference
    {
        return ChargeReference::fromNative((int) $this->charge_id);
    }

    /**
     * Gets the shop for the charge.
     *
     * @return BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(
            getShopifyConfig('user_model'),
            'user_id',
            'id'
        );
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
    public function getTypeApiString($plural = false): string
    {
        $types = [
            ChargeType::CREDIT()->toNative()    => 'application_credit',
            ChargeType::CHARGE()->toNative()    => 'application_charge',
            ChargeType::RECURRING()->toNative() => 'recurring_application_charge',
        ];
        $type = $types[$this->getType()->toNative()];

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
     * Get the charge type.
     *
     * @return ChargeType
     */
    public function getType(): ChargeType
    {
        return ChargeType::fromNative($this->type);
    }

    /**
     * Checks if the charge is a type.
     *
     * @param ChargeType $type The charge type.
     *
     * @return bool
     */
    public function isType(ChargeType $type): bool
    {
        return $this->getType()->isSame($type);
    }

    /**
     * Checks if the charge is a trial-type charge.
     *
     * @return bool
     */
    public function isTrial(): bool
    {
        return ! is_null($this->trial_ends_on);
    }

    /**
     * Get the charge status.
     *
     * @return ChargeStatus
     */
    public function getStatus(): ChargeStatus
    {
        return ChargeStatus::fromNative($this->status);
    }

    /**
     * Checks the status of the charge.
     *
     * @param ChargeStatus $status The status to check.
     *
     * @return bool
     */
    public function isStatus(ChargeStatus $status): bool
    {
        return $this->getStatus()->isSame($status);
    }

    /**
     * Checks if the charge is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isStatus(ChargeStatus::ACTIVE());
    }

    /**
     * Checks if the charge was accepted (for one-time and reccuring).
     *
     * @return bool
     */
    public function isAccepted(): bool
    {
        return $this->isStatus(ChargeStatus::ACCEPTED());
    }

    /**
     * Checks if the charge was declined (for one-time and reccuring).
     *
     * @return bool
     */
    public function isDeclined(): bool
    {
        return $this->isStatus(ChargeStatus::DECLINED());
    }

    /**
     * Checks if the charge was cancelled.
     *
     * @return bool
     */
    public function isCancelled(): bool
    {
        return ! is_null($this->cancelled_on) || $this->isStatus(ChargeStatus::CANCELLED());
    }

    /**
     * Checks if the charge is "active" (non-API check).
     *
     * @return bool
     */
    public function isOngoing(): bool
    {
        return $this->isActive() && ! $this->isCancelled();
    }
}
