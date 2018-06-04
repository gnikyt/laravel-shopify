<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class Charge extends Model
{
    use SoftDeletes;

    // Types of charges
    const CHARGE_RECURRING = 1;
    const CHARGE_ONETIME = 2;
    const CHARGE_USAGE = 3;
    const CHARGE_CREDIT = 4;

    /**
     * Scope for latest charge for a shop.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query The query builder
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
     * @param integer                               $type The type of charge
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
}
