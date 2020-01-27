<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper;
use OhMyBrew\ShopifyApp\Contracts\Queries\Plan as PlanQuery;
use OhMyBrew\ShopifyApp\Contracts\Queries\Shop as ShopQuery;
use OhMyBrew\ShopifyApp\Objects\Values\NullablePlanId;
use OhMyBrew\ShopifyApp\Objects\Values\ShopId;

/**
 * Retrieve the a billing plan's URL.
 */
class GetPlanUrl
{
    /**
     * The API helper.
     *
     * @var ApiHelper
     */
    protected $apiHelper;

    /**
     * Querier for plans.
     *
     * @var PlanQuery
     */
    protected $planQuery;

    /**
     * Querier for shops.
     *
     * @var ShopQuery
     */
    protected $shopQuery;

    /**
     * Setup.
     *
     * @param ApiHelper $apiHelper The API helper.
     * @param PlanQuery $planQuery The querier for the plans.
     * @param ShopQuery $shopQuery The querier for shops.
     *
     * @return self
     */
    public function __construct(ApiHelper $apiHelper, PlanQuery $planQuery, ShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
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
        if (is_null($planId)) {
            $plan = $this->planQuery->getDefault();
        }

        $api = $this
            ->apiHelper
            ->setInstance($shop->api())
            ->createCharge(
                $plan->getTypeAsString(true),
                $plan->chargeDetails($shop)
            );

        return $api->confirmation_url;
    }
}
