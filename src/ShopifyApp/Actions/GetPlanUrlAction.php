<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Services\IApiHelper;
use OhMyBrew\ShopifyApp\Interfaces\IPlanQuery;
use OhMyBrew\ShopifyApp\Interfaces\IShopQuery;

/**
 * Retrieve the a billing plan's URL.
 */
class GetPlanUrlAction
{
    /**
     * The API helper.
     *
     * @var IApiHelper
     */
    protected $apiHelper;

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
     * Setup.
     *
     * @param IApiHelper $apiHelper The API helper.
     * @param IPlanQuery $planQuery The querier for the plans.
     * @param IShopQuery $shopQuery The querier for shops.
     *
     * @return self
     */
    public function __construct(IApiHelper $apiHelper, IPlanQuery $planQuery, IShopQuery $shopQuery)
    {
        $this->apiHelper = $apiHelper;
        $this->planQuery = $planQuery;
        $this->shopQuery = $shopQuery;
    }

    /**
     * Execution.
     *
     * @param string   $shopDomain The shop's domain.
     * @param int|null $planId     The plan to present.
     *
     * @return mixed
     */
    public function __invoke(string $shopDomain, ?int $planId)
    {
        // Get the shop
        $shop = $this->shopQuery->getByDomain(ShopifyApp::sanitizeShopDomain($shopDomain));

        // If the plan is null, get a plan
        if (is_null($planId)) {
            $plan = $this->planQuery->getDefault();
        }

        $api = $this
            ->apiHelper
            ->setInstance($shop->api())
            ->createCharge($plan->getTypeAsString(true), $plan->chargeDetails($shop));
        return $api->confirmation_url;
    }
}
