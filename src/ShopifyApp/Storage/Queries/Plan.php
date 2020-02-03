<?php

namespace OhMyBrew\ShopifyApp\Storage\Queries;

use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as PlanQuery;
use OhMyBrew\ShopifyApp\Models\Plan as PlanModel;

/**
 * Reprecents plan queries.
 */
class Plan implements PlanQuery
{
    /**
     * {@inheritdoc}
     */
    public function getByID(PlanId $planId, array $with = []): ?self
    {
        return PlanModel::with($with)
            ->get()
            ->where('id', $planId->toNative())
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(array $with = []): ?self
    {
        return PlanModel::with($with)
            ->get()
            ->where('on_install', true)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(array $with = []): array
    {
        return PlanModel::with($with)
            ->get()
            ->all();
    }
}
