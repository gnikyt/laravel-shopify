<?php

namespace OhMyBrew\ShopifyApp\Traits;

use Illuminate\Support\Facades\Config;
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
        // Get the job class and dispatch
        $jobClass = Config::get('shopify-app.job_namespace') . str_replace('-', '', ucwords($type, '-')) . 'Job';
        $jobData = json_decode(Request::getContent());

        $jobClass::dispatch(
            Request::header('x-shopify-shop-domain'),
            $jobData
        );

        return Response::make('', 201);
    }
}
