<?php

namespace OhMyBrew\ShopifyApp\Contracts\Queries;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Storage\Models\Plan as PlanModel;

/**
 * Reprecents a queries for plans.
 */
interface Plan
{
    /**
     * Get by ID.
     *
     * @param PlanId $planId The plan ID.
     * @param array  $with   The relations to eager load.
     *
     * @return PlanModel|null
     */
    public function getById(PlanId $planId, array $with = []): ?PlanModel;

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
