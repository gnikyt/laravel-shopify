<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Osiset\ShopifyApp\Storage\Models\Plan;
use Osiset\ShopifyApp\Util;

/**
 * Responsible for showing the main homescreen for the app.
 */
trait ApiController
{
    /**
     * 200 Response.
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json();
    }

    /**
     * Returns authenticated users details.
     *
     * @return JsonResponse
     */
    public function getSelf(): JsonResponse
    {
        return response()->json(Auth::user()->only([
            'name',
            'shopify_grandfathered',
            'shopify_freemium',
            'plan',
        ]));
    }

    /**
     * Returns currently available plans.
     *
     * @return JsonResponse
     */
    public function getPlans(): JsonResponse
    {
        $planClass = Util::getShopifyConfig('models.plan', Plan::class);
        $planModel = new $planClass();

        return response()->json($planModel->all());
    }
}
