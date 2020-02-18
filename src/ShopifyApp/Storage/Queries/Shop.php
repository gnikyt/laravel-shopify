<?php

namespace OhMyBrew\ShopifyApp\Storage\Queries;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Contracts\ShopModel;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Reprecents shop queries.
 */
class Shop implements IShopQuery
{
    use ConfigAccessible;

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
        $this->model = $this->getConfig('user_model');
    }

    /**
     * {@inheritdoc}
     */
    public function getByID(ShopId $shopId, array $with = [], bool $withTrashed = false): ?ShopModel
    {
        $result = $this->model::with($with);
        if ($withTrashed) {
            $result = $result->withTrashed();
        }

        return $result
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
            $result = $result->withTrashed();
        }

        return $result
            ->get()
            ->where('name', $domain->toNative())
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(array $with = []): Collection
    {
        return $this->model::with($with)
            ->get();
    }
}
