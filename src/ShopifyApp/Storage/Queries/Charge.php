<?php

namespace OhMyBrew\ShopifyApp\Storage\Queries;

use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Storage\Models\Charge as ChargeModel;

/**
 * Reprecents a queries for charges.
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
    public function getByChargeIdAndShopId(ChargeId $chargeId, ShopId $shopId): ?ChargeModel
    {
        return ChargeModel
            ::where('charge_id', $chargeId->toNative())
            ->where('user_id', $shopId->toNative())
            ->get()
            ->first();
    }
}
