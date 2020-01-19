<?php

namespace OhMyBrew\ShopifyApp\Commands;

use OhMyBrew\ShopifyApp\Interfaces\IShopCommand;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
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
        $shop = $this->getShop($shopId);
        $shop->plan_id = $planId;
        $shop->freemium = false;

        return $shop->save();
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessToken(int $shopId, string $token): bool
    {
        $shop = $this->getShop($shopId);
        $this->shop->shopify_token = $token;

        return $this->shop->save();
    }

    /**
     * Helper to get the shop.
     *
     * @param int $shopId The shop's ID.
     *
     * @return IShopModel
     */
    protected function getShop(int $shopId): IShopModel
    {
        return $this->query->getById($shopId);
    }
}
