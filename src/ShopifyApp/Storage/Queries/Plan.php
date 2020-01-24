<?php

namespace OhMyBrew\ShopifyApp\Queries;

use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Models\Plan as PlanModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as PlanQuery;


/**
 * Reprecents plan queries.
 */
class Plan implements PlanQuery
{
    /**
     * {@inheritDoc}
     */
    public function getByID(PlanId $id, array $with = []): ?Plan
    {
        return PlanModel::with($with)
            ->get()
            ->where('id', $id)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefault(array $with = []): ?Plan
    {
        return PlanModel::with($with)
            ->get()
            ->where('on_install', true)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function getAll(array $with = []): array
    {
        return PlanModel::with($with)
            ->get()
            ->all();
    }
}
