<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use Illuminate\Support\Collection;

/**
 * Reprecents a queries for shops.
 */
interface IShopQuery
{
    /**
     * Get by ID.
     *
     * @param int   $id   The shop ID.
     * @param array $with The relations to eager load.
     *
     * @return IShopModel|null
     */
    public function getByID(int $id, array $with = []): ?IShopModel;

    /**
     * Get by domain.
     *
     * @param string $domain The shop domain.
     * @param array  $with   The relations to eager load.
     *
     * @return IShopModel|null
     */
    public function getByDomain(string $domain, array $with = []): ?IShopModel;

    /**
     * Get all records.
     *
     * @param array $with The relations to eager load.
     *
     * @return Collection IShopModel[]
     */
    public function getAll(array $with = []): Collection;
}
