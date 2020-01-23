<?php

namespace OhMyBrew\ShopifyApp\Queries;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Reprecents shop queries.
 */
class ShopQuery implements IShopQuery
{
    /**
     * The shop model (configurable).
     *
     * @var IShopModel
     */
    protected $model;

    /**
     * Setup.
     * 
     * @param string $model The configurable model for the shop.
     *
     * @return self
     */
    public function __construct(string $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritDoc}
     */
    public function getByID(int $id, array $with = []): ?IShopModel
    {
        return $this->model::with($with)
            ->get()
            ->where('id', $id)
            ->first();
    }


    /**
     * {@inheritDoc}
     */
    public function getByDomain(string $domain, array $with = []): ?IShopModel
    {
        return $this->model::with($with)
            ->get()
            ->where('shopify_domain', $domain)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $with = []): array
    {
        return $this->model::with($with)
            ->get()
            ->all();
    }
}