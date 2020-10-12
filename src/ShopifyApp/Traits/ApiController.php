<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Osiset\ShopifyApp\Storage\Models\Plan;

/**
 * Responsible for showing the main homescreen for the app.
 */
trait ApiController
{
    /**
     * 200 Response
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json();
    }

    public function getSelf() : JsonResponse
    {
        return response()->json(Auth::user()->only([
            'name',
            'shopify_grandfathered',
            'shopify_freemium',
            'plan',
        ]));
    }

    public function getPlans() : JsonResponse
    {
        return response()->json(Plan::all());
    }
}
