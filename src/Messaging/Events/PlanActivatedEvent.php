<?php

namespace Osiset\ShopifyApp\Messaging\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Objects\Values\ChargeId;

/**
 * Event fired when this
 */
class PlanActivatedEvent
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
     * Plan's instance.
     *
     * @var Model
     */
    public $plan;

    /**
     * Charge ID
     *
     * @var ChargeId
     */
    public $chargeId;

    /**
     * Create a new event instance.
     *
     * @param IShopModel $shop
     *
     * @return void
     */
    public function __construct(IShopModel $shop, Model $plan, ChargeId $chargeId)
    {
        $this->shop = $shop;
        $this->plan = $plan;
        $this->chargeId = $chargeId;
    }
}
