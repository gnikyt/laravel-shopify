<?php

namespace OhMyBrew\ShopifyApp\Commands;

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
    public function setToPlan(int $shopId, int $planId): bool
    {
        $shop = $this->query->getById($shopId);
        $shop->plan_id = $planId;
        $shop->freemium = false;

        return $shop->save();
    }
}
