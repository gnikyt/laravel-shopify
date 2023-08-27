<?php

namespace Osiset\ShopifyApp\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;

/**
 * Event fired when this
 */
class AppUninstalledEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Shop's instance.
     *
     * @var IShopModel
     */
    public $shop;

    /**
     * Create a new event instance.
     *
     * @param IShopModel $shop
     *
     * @return void
     */
    public function __construct(IShopModel $shop)
    {
        $this->shop = $shop;
    }
}
