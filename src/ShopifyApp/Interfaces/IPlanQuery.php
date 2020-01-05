<?php

namespace OhMyBrew\ShopifyApp\Interfaces;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Models\Plan;

/**
 * Reprecents a queries for plans.
 */
interface IPlanQuery
{
    /**
     * Get by ID.
     *
     * @param int   $id   The plan ID.
     * @param array $with The relations to eager load.
     *
     * @return Plan|null
     */
    public function getByID(int $id, array $with = []): ?Plan;

    /**
     * Get default on-install plan.
     *
     * @param array $with The relations to eager load.
     *
     * @return Plan|null
     */
    public function getDefault(array $with = []): ?Plan;

    /**
     * Get all records.
     *
     * @param array $with The relations to eager load.
     *
     * @return Collection Plan[]
     */
    public function getAll(array $with = []): Collection;
}
