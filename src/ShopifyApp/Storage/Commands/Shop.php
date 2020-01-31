<?php

namespace OhMyBrew\ShopifyApp\Storage\Commands;

use OhMyBrew\ShopifyApp\Contracts\ShopModel;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as ShopCommand;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;

/**
 * Reprecents the commands for shops.
 */
class Shop implements ShopCommand
{
    /**
     * The querier.
     *
     * @var ShopQuery
     */
    protected $query;

    /**
     * Init for shop command.
     */
    public function __construct(ShopQuery $query)
    {
        $this->query = $query;
    }

    /**
     * {@inheritDoc}
     */
    public function setToPlan(ShopId $shopId, PlanId $planId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->plan_id = $planId;
        $shop->freemium = false;

        return $shop->save();
    }

    /**
     * {@inheritDoc}
     */
    public function setAccessToken(ShopId $shopId, AccessToken $token): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_token = $token;

        return $shop->save();
    }

    /**
     * {@inheritDoc}
     */
    public function clean(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_token = null;
        $shop->plan_id = null;
        
        return $shop->save();
    }

    /**
     * {@inheritDoc}
     */
    public function softDelete(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->delete();
        
        return $shop->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function restore(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->restore();

        return $shop->restore();
    }

    /**
     * {@inheritDoc}
    */
    public function setAsFreemium(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_freemium = true;

        return $shop->save();
    }

    /**
     * {@inheritDoc}
    */
    public function setNamespace(ShopId $shopId, string $namespace): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_namespace = $namespace;

        return $shop->save();
    }

    /**
     * Helper to get the shop.
     *
     * @param int $shopId The shop's ID.
     *
     * @return ShopModel|null
     */
    protected function getShop(ShopId $shopId): ?ShopModel
    {
        return $this->query->getById($shopId);
    }
}
