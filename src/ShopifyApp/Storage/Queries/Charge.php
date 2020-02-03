<?php

namespace OhMyBrew\ShopifyApp\Storage\Queries;

use OhMyBrew\ShopifyApp\Models\Charge as ChargeModel;
use OhMyBrew\ShopifyApp\Contracts\Queries\Charge as ChargeQuery;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Reprecents a queries for charges.
 */
class Charge implements ChargeQuery
{
    /**
     * {@inheritDoc}
     */
    public function getById(ChargeId $chargeId, array $with = []): ?ChargeModel
    {
        return ChargeModel::with($with)
            ->where('charge_id', $chargeId->toNative());
    }

    /**
     * {@inheritDoc}
     */
    public function getByShopIdAndChargeId(ShopId $shopId, ChargeId $chargeId): ?ChargeModel
    {
        return ChargeModel::where(
            [
                'shop_id'   => $shopId->toNative(),
                'charge_id' => $chargeId->toNative(),
            ]
        )->get();
    }
}
