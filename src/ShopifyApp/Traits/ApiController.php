<?php

namespace Osiset\ShopifyApp\Traits;

use Illuminate\Http\JsonResponse;

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
        return response()->json(Auth::user());
    }
}
