<?php

namespace OhMyBrew\ShopifyApp\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use OhMyBrew\ShopifyApp\Contracts\ShopModel as IShopModel;

/**
 * Event fired when a shop passes through authentication.
 */
class AppLoggedIn
{
    use Dispatchable;
    use SerializesModels;
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
