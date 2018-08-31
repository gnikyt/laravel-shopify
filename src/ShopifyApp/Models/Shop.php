<?php

namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Scopes\NamespaceScope;

class Shop extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shopify_domain',
        'shopify_token',
        'grandfathered',
        'namespace',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
     * The API instance.
     *
     * @var object
     */
    protected $api;

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new NamespaceScope());
    }

    /**
     * Creates or returns an instance of API for the shop.
     *
     * @return object
     */
    public function api()
    {
        if (!$this->api) {
            // Create new API instance
            $api = ShopifyApp::api();
            $api->setSession($this->shopify_domain, $this->shopify_token);

            $this->api = $api;
        }

        // Return existing instance
        return $this->api;
    }

    /**
     * Checks is shop is grandfathered in.
     *
     * @return bool
     */
    public function isGrandfathered()
    {
        return ((bool) $this->grandfathered) === true;
    }

    /**
     * Get charges.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function charges()
    {
        return $this->hasMany('OhMyBrew\ShopifyApp\Models\Charge');
    }

    /**
     * Checks if charges have been applied to the shop.
     *
     * @return bool
     */
    public function hasCharges()
    {
        return $this->charges->isNotEmpty();
    }
}
