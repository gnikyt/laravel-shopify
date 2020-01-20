<?php

namespace OhMyBrew\ShopifyApp\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;

/**
 * Event fired when a shop passes through authentication.
 */
class AppLoggedIn
{
    use Dispatchable, SerializesModels;

    /**
     * Shop's instance.
     *
     * @var string
     */
    protected $shop;

    /**
     * Create a new evebt instance.
     *
     * @param IShopModel $shop The shop.
     *
     * @return void
     */
    public function __construct(IShopModel $shop)
    {
        $this->shop = $shop;
    }
}
