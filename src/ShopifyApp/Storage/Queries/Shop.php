<?php

namespace OhMyBrew\ShopifyApp\Queries;

use OhMyBrew\ShopifyApp\Contracts\ShopModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Reprecents shop queries.
 */
class Shop implements ShopQuery
{
    /**
     * The shop model (configurable).
     *
     * @var ShopModel
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
    public function getByID(int $id, array $with = []): ?ShopModel
    {
        return $this->model::with($with)
            ->get()
            ->where('id', $id)
            ->first();
    }


    /**
     * {@inheritDoc}
     */
    public function getByDomain(ShopDomain $domain, array $with = []): ?ShopModel
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