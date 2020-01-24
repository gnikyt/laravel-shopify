<?php

namespace OhMyBrew\ShopifyApp\Contracts\Queries;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Interfaces\ShopModel;
use OhMyBrew\ShopifyApp\Objects\Values\ShopDomain;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Reprecents a queries for shops.
 */
interface Shop
{
    /**
     * Get by ID.
     *
     * @param ShopId $id   The shop ID.
     * @param array  $with The relations to eager load.
     *
     * @return ShopModel|null
     */
    public function getById(ShopId $id, array $with = []): ?ShopModel;

    /**
     * Get by domain.
     *
     * @param ShopDomain $domain The shop domain.
     * @param array      $with   The relations to eager load.
     *
     * @return ShopModel|null
     */
    public function getByDomain(ShopDomain $domain, array $with = []): ?IShopModel;

    /**
     * Get all records.
     *
     * @param array $with The relations to eager load.
     *
     * @return Collection ShopModel[]
     */
    public function getAll(array $with = []): Collection;
}
