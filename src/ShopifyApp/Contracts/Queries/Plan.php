<?php

namespace OhMyBrew\ShopifyApp\Contracts\Queries;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Models\Plan as PlanModel;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;

/**
 * Reprecents a queries for plans.
 */
interface Plan
{
    /**
     * Get by ID.
     *
     * @param PlanId $id   The plan ID.
     * @param array  $with The relations to eager load.
     *
     * @return PlanModel|null
     */
    public function getById(PlanId $id, array $with = []): ?PlanModel;

    /**
     * Get default on-install plan.
     *
     * @param array $with The relations to eager load.
     *
     * @return PlanModel|null
     */
    public function getDefault(array $with = []): ?PlanModel;

    /**
     * Get all records.
     *
     * @param array $with The relations to eager load.
     *
     * @return Collection Plan[]
     */
    public function getAll(array $with = []): Collection;
}
