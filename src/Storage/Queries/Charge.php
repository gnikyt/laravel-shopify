<?php

namespace Osiset\ShopifyApp\Storage\Queries;

use Osiset\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Storage\Models\Charge as ChargeModel;

/**
 * Represents a queries for charges.
 */
class Charge implements IChargeQuery
{
    /**
     * {@inheritdoc}
     */
    public function getById(ChargeId $chargeId, array $with = []): ?ChargeModel
    {
        return ChargeModel::with($with)
            ->where('id', $chargeId->toNative())
            ->get()
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByReference(ChargeReference $chargeRef, array $with = []): ?ChargeModel
    {
        return ChargeModel::with($with)
            ->where('charge_id', $chargeRef->toNative())
            ->withTrashed()
            ->get()
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByReferenceAndShopId(ChargeReference $chargeRef, ShopId $shopId): ?ChargeModel
    {
        return ChargeModel::query()
            ->where('charge_id', $chargeRef->toNative())
            ->where('user_id', $shopId->toNative())
            ->get()
            ->first();
    }
}
