<?php

namespace OhMyBrew\ShopifyApp\Queries;

use Illuminate\Support\Facades\Config;
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
     * @return self
     */
    public function __construct()
    {
        $this->model = Config::get('auth.providers.users.model');
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