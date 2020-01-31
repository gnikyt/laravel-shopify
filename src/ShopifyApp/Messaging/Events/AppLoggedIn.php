<?php

namespace OhMyBrew\ShopifyApp\Messaging\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;

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
     * @return self
     */
    public function __construct(IShopModel $shop)
    {
        $this->shop = $shop;
    }
}
