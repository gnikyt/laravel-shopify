<?php

namespace OhMyBrew\ShopifyApp\Commands;

use OhMyBrew\ShopifyApp\DTO\ShopSetPlanDTO;
use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Reprecents the commands for shops.
 */
class ShopCommand implements IShopCommand
{
    /**
     * The querier.
     *
     * @var IShopQuery
     */
    protected $query;

    /**
     * Init for shop command.
     */
    public function __construct(IShopQuery $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function setToPlan(ShopSetPlanDTO $setObj): bool
    {
        $shop = $this->query->getById($setObj->shopId);
        $shop->plan_id = $setObj->planId;
        $shop->freemium = false;

        return $shop->save();
    }
}
