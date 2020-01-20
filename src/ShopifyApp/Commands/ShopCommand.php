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
        $shop->shopify_token = $token;

        return $shop->save();
    }

    /**
     * {@inheritDoc}
     */
    public function clean(int $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_token = null;
        $shop->plan_id = null;
        
        return $shop->save();
    }

    /**
     * {@inheritDoc}
     */
    public function softDelete(int $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->delete();
        
        return $shop->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(int $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->restore();

        return $shop->restore();
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
