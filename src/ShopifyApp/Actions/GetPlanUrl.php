<?php

namespace OhMyBrew\ShopifyApp\Actions;

use OhMyBrew\ShopifyApp\Interfaces\IPlanQuery;
use OhMyBrew\ShopifyApp\Interfaces\IShopModel;

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
     * Setup.
     *
     * @param IPlanQuery $planQuery The querier for the plans.
     *
     * @return self
     */
    public function __construct(IPlanQuery $planQuery)
    {
        $this->planQuery = $planQuery;
    }

    /**
     * Execution.
     *
     * @param IShopModel $shop   The shop to present the plan to.
     * @param int|null   $planId The plan to present.
     *
     * @return mixed
     */
    public function __invoke(IShopModel $shop, ?int $planId)
    {
        // If the plan is null, get a plan
        if (is_null($planId)) {
            $plan = $this->planQuery->getDefault();
        }

        // Begin the charge request
        $charge = $shop->api()->rest(
            'POST',
            "/admin/{$this->plan->tiypeAsString(true)}.json",
            [
                "{$this->plan->typeAsString()}" => $plan->chargeDetails($shop),
            ]
        )->body->{$this->plan->typeAsString()};

        return $charge->confirmation_url;
    }
}
