<?php

namespace Osiset\ShopifyApp\Storage\Queries;

use Osiset\ShopifyApp\Contracts\Queries\Charge as IChargeQuery;
use Osiset\ShopifyApp\Objects\Values\ChargeId;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Storage\Models\Charge as ChargeModel;
use Osiset\ShopifyApp\Util;

/**
 * Represents a queries for charges.
 */
class Charge implements IChargeQuery
{
    /**
     * the Charge Model.
     *
     * @var ChargeModel
     */
    protected $chargeModel;

    /**
     * Init for charge command.
     */
    public function __construct()
    {
        $chargeClass = Util::getShopifyConfig('models.charge', ChargeModel::class);
        $this->chargeModel = new $chargeClass();
    }


    /**
     * {@inheritdoc}
     */
    public function getById(ChargeId $chargeId, array $with = []): ?ChargeModel
    {
        return $this->chargeModel->with($with)
            ->where('id', $chargeId->toNative())
            ->get()
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getByReference(ChargeReference $chargeRef, array $with = []): ?ChargeModel
    {
        return $this->chargeModel->with($with)
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
        return $this->chargeModel->query()
            ->where('charge_id', $chargeRef->toNative())
            ->where('user_id', $shopId->toNative())
            ->get()
            ->first();
    }
}
