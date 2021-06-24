<?php

namespace Osiset\ShopifyApp\Storage\Commands;

use Osiset\ShopifyApp\Contracts\Commands\Shop as ShopCommand;
use Osiset\ShopifyApp\Contracts\Objects\Values\AccessToken as AccessTokenValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\PlanId as PlanIdValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopId as ShopIdValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel;
use Osiset\ShopifyApp\Util;

/**
 * Reprecents the commands for shops.
 */
class Shop implements ShopCommand
{
    /**
     * The shop model (configurable).
     *
     * @var ShopModel
     */
    protected $model;

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
        $this->model = Util::getShopifyConfig('user_model');
    }

    /**
     * {@inheritdoc}
     */
    public function make(ShopDomainValue $domain, AccessTokenValue $token): ShopIdValue
    {
        $model = $this->model;
        $shop = new $model();
        $shop->name = $domain->toNative();
        $shop->password = $token->isNull() ? '' : $token->toNative();
        $shop->email = "shop@{$domain->toNative()}";
        $shop->save();

        return $shop->getId();
    }

    /**
     * {@inheritdoc}
     */
    public function setToPlan(ShopIdValue $shopId, PlanIdValue $planId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->plan_id = $planId->toNative();
        $shop->shopify_freemium = false;

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function setAccessToken(ShopIdValue $shopId, AccessTokenValue $token): bool
    {
        $shop = $this->getShop($shopId);
        $shop->password = $token->toNative();

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function clean(ShopIdValue $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->password = '';
        $shop->plan_id = null;

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function softDelete(ShopIdValue $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $shop->charges()->delete();

        return $shop->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function restore(ShopIdValue $shopId): bool
    {
        $shop = $this->getShop($shopId, true);
        $shop->charges()->restore();

        return $shop->restore();
    }

    /**
     * {@inheritdoc}
     */
    public function setAsFreemium(ShopIdValue $shopId): bool
    {
        $shop = $this->getShop($shopId);
        $this->setAsFreemiumByRef($shop);

        return $shop->save();
    }

    /**
     * {@inheritdoc}
     */
    public function setNamespace(ShopIdValue $shopId, string $namespace): bool
    {
        $shop = $this->getShop($shopId);
        $this->setNamespaceByRef($shop, $namespace);

        return $shop->save();
    }

    /**
     * Sets a shop as freemium.
     *
     * @param ShopModel $shop The shop model (reference).
     *
     * @return void
     */
    public function setAsFreemiumByRef(ShopModel &$shop): void
    {
        $shop->shopify_freemium = true;
    }

    /**
     * Sets a shop namespace.
     *
     * @param ShopModel $shop      The shop model (reference).
     * @param string    $namespace The namespace.
     *
     * @return void
     */
    public function setNamespaceByRef(ShopModel &$shop, string $namespace): void
    {
        $shop->shopify_namespace = $namespace;
    }

    /**
     * Helper to get the shop.
     *
     * @param ShopIdValue $shopId      The shop's ID.
     * @param bool        $withTrashed Include trashed shops?
     *
     * @return ShopModel|null
     */
    protected function getShop(ShopIdValue $shopId, bool $withTrashed = false): ?ShopModel
    {
        return $this->query->getById($shopId, [], $withTrashed);
    }
}
