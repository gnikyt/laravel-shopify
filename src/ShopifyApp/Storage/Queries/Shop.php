<?php

namespace OhMyBrew\ShopifyApp\Storage\Queries;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Contracts\ShopModel;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

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
     * {@inheritdoc}
     */
    public function getByID(ShopId $shopId, array $with = []): ?ShopModel
    {
        return $this->model::with($with)
            ->get()
            ->where('id', $shopId->toNative())
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByDomain(ShopDomain $domain, array $with = [], bool $withTrashed = false): ?ShopModel
    {
        $result = $this->model::with($with);
        if ($withTrashed) {
            $result = $result::withTrashed();
        }

        return $result
            ->get()
            ->where('shopify_domain', $domain->toNative())
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(array $with = []): array
    {
        return $this->model::with($with)
            ->get()
            ->all();
    }
}
