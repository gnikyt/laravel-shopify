<?php

namespace Osiset\ShopifyApp\Messaging\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopId;

/**
 * Event fired when this
 */
class AppInstalledEvent
{
    use Dispatchable;
    use SerializesModels;

    /**
     * Shop's instance.
     *
     * @var ShopId
     */
    public $shopId;

    /**
     * Create a new event instance.
     *
     * @param ShopId $shop_id
     *
     * @return void
     */
    public function __construct(ShopId $shop_id)
    {
        $this->shopId = $shop_id;
    }
}
