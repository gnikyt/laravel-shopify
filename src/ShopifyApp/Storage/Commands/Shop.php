<?php

namespace OhMyBrew\ShopifyApp\Storage\Commands;

use OhMyBrew\ShopifyApp\Contracts\Commands\Shop as ShopCommand;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Contracts\ShopModel;
use OhMyBrew\ShopifyApp\Objects\Values\AccessToken;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

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
     * {@inheritdoc}
     */
    public function setToPlan(ShopId $shopId, PlanId $planId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->plan_id = $planId->toNative();
        $shop->shopify_freemium = false;

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken(ShopId $shopId, AccessToken $token): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_token = $token->toNative();

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function clean(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_token = null;
        $shop->plan_id = null;

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function softDelete(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->delete();

        return $shop->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function restore(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->restore();

        return $shop->restore();
    }

    /**
     * {@inheritdoc}
     */
    public function setAsFreemium(ShopId $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->shopify_freemium = true;

        return $shop->save();
    }

    /**
     * {@inheritdoc}
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
