<?php

namespace Osiset\ShopifyApp\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;

class AppInstalled
{
    use Dispatchable, SerializesModels;

    /**
     * Shop's instance.
     *
     * @var string
     */
    protected $shop;

    /**
     * Create a new event instance.
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
