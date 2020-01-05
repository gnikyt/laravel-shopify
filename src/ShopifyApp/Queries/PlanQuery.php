<?php

namespace OhMyBrew\ShopifyApp\Queries;

use OhMyBrew\ShopifyApp\Models\Plan;
use OhMyBrew\ShopifyApp\Interfaces\IPlanQuery;


/**
 * Reprecents plan queries.
 */
class PlanQuery implements IPlanQuery
{
    /**
     * {@inheritDoc}
     */
    public function getByID(int $id, array $with = []): ?Plan
    {
        return Plan::with($with)
            ->get()
            ->where('id', $id)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefault(array $with = []): ?Plan
    {
        return Plan::with($with)
            ->get()
            ->where('on_install', true)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $with = []): array
    {
        return Plan::with($with)
            ->get()
            ->all();
    }
}
