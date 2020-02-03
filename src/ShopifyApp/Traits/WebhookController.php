<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\Response as ResponseResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;

/**
 * Responsible for handling incoming webhook requests.
 */
trait WebhookController
{
    /**
     * Handles an incoming webhook.
     * TODO: Figure out a way to pass dependencies.
     *
     * @param string  $type    The type of webhook
     * @param Request $request The request object.
     *
     * @return ResponseResponse
     */
    public function handle($type, Request $request): ResponseResponse
    {
        // Get the job class and dispatch
        $jobClass = Config::get('shopify-app.job_namespace').str_replace('-', '', ucwords($type, '-')).'Job';
        $jobData = json_decode($request->getContent());

        $jobClass::dispatch(
            $request->header('x-shopify-shop-domain'),
            $jobData
        );

        return Response::make('', 201);
    }
}
