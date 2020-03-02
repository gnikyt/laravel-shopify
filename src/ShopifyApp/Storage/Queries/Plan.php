<?php

namespace OhMyBrew\ShopifyApp\Storage\Queries;

use Illuminate\Support\Collection;
use OhMyBrew\ShopifyApp\Contracts\Objects\Values\PlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use OhMyBrew\ShopifyApp\Storage\Models\Plan as PlanModel;

/**
 * Reprecents plan queries.
 */
class Plan implements IPlanQuery
{
    /**
     * {@inheritdoc}
     */
    public function getById(PlanId $planId, array $with = []): ?PlanModel
    {
        return PlanModel::with($with)
            ->get()
            ->where('id', $planId->toNative())
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(array $with = []): ?PlanModel
    {
        return PlanModel::with($with)
            ->get()
            ->where('on_install', true)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAll(array $with = []): Collection
    {
        return PlanModel::with($with)
            ->get();
    }
}
