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
        // Get the job class and dispatch
        $jobClass = '\\App\\Jobs\\'.str_replace('-', '', ucwords($type, '-')).'Job';
        $jobData = json_decode(Request::getContent());

        ! config('shopify-app.debug')
            ?: logger(get_class() . " try to dispatch $jobClass with ".print_r($jobData, true));

        $jobClass::dispatch(
            Request::header('x-shopify-shop-domain'),
            $jobData
        );

        return Response::make('', 201);
    }
}
