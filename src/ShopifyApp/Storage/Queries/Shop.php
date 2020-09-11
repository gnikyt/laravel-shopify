<?php

namespace Osiset\ShopifyApp\Storage\Queries;

use Illuminate\Support\Collection;
use Osiset\ShopifyApp\Contracts\Objects\Values\ShopDomain as ShopDomainValue;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Traits\ConfigAccessible;

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
     * @return void
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
    public function getByDomain(ShopDomainValue $domain, array $with = [], bool $withTrashed = false): ?ShopModel
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
