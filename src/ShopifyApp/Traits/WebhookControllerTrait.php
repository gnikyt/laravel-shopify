<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

/**
 * Responsible for handling incoming webhook requests.
 */
trait WebhookControllerTrait
{
    /**
     * Handles an incoming webhook.
     *
     * @param string $type The type of webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function handle($type)
    {
        $shopDomain = Request::header('x-shopify-shop-domain');
        // Get the job class and dispatch
        $jobClass = '\\App\\Jobs\\'.str_replace('-', '', ucwords($type, '-')).'Job';
        if (!class_exists($jobClass)) {
            \Log::error(get_class().' - '.$jobClass.' webhook not exists');

            return Response::make('', 404);
        }

        $jobClass::dispatch(
            $shopDomain,
            json_decode(Request::getContent())
        );

        !config('shopify-app.debug') ?: \Log::info(get_class() . ' - ' . $jobClass . ' webhook dispatched');

        return Response::make('', 201);
    }
}
