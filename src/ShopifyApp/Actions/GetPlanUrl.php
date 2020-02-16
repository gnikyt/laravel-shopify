<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Objects\Values\ShopId;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as IPlanQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Services\ChargeHelper;

/**
 * Retrieve the a billing plan's URL.
 */
class GetPlanUrl
{
    /**
     * Querier for plans.
     *
     * @var IPlanQuery
     */
    protected $planQuery;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * The charge helper.
     *
     * @var ChargeHelper
     */
    protected $chargeHelper;

    /**
     * Setup.
     *
     * @param ChargeHelper $chargeHelper The charge helper.
     * @param IPlanQuery   $planQuery    The querier for the plans.
     * @param IShopQuery   $shopQuery    The querier for shops.
     *
     * @return self
     */
    public function __construct(ChargeHelper $chargeHelper, IPlanQuery $planQuery, IShopQuery $shopQuery)
    {
        $this->chargeHelper = $chargeHelper;
        $this->planQuery = $planQuery;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     * TODO: Rethrow an API exception.
     *
     * @param ShopId         $shopId The shop ID.
     * @param NullablePlanId $planId The plan to present.
     *
     * @return string
     */
    public function __invoke(ShopId $shopId, NullablePlanId $planId): string
    {
        // Get the shop
        $shop = $this->shopQuery->getById($shopId);

        // If the plan is null, get a plan
        if ($planId->isNull()) {
            $plan = $this->planQuery->getDefault();
        }

        $api = $shop->apiHelper()->createCharge(
            ChargeType::fromNative($plan->getType()->toNative()),
            $this->chargeHelper->details($plan, $shop)
        );

        return $api->confirmation_url;
    }
}
