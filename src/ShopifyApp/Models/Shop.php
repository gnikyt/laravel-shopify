<?php namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;

class Shop extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'shopify_domain',
        'shopify_token'
    ];

    /**
     * The API instance
     *
     * @var object
     */
    protected $api;

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
}
