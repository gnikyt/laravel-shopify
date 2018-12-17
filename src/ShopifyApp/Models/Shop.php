<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;
use OhMyBrew\ShopifyApp\Traits\ShopModelTrait;

/**
 * Responsible for reprecenting a shop record.
 */
class Shop extends Model
{
    use ShopModelTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shopify_domain',
        'shopify_token',
        'grandfathered',
        'freemium',
        'plan_id',
        'namespace',
    ];

    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'grandfathered' => 'bool',
        'freemium'      => 'bool',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];
}
