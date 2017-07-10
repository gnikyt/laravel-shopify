<?php namespace OhMyBrew\ShopifyApp\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
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
            $configClass = config('shopify-app.api_class');
            $this->api = new $configClass;
        }

        // Return existing instance
        return $this->api;
    }
}
