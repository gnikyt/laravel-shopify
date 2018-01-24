<?php namespace OhMyBrew\ShopifyApp\Traits;

trait WebhookControllerTrait
{
    /**
     * Handles an incoming webhook
     *
     * @param string $type The type of webhook
     *
     * @return \Illuminate\Http\Response
     */
    public function handle($type)
    {
        $classPath = $this->getJobClassFromType($type);
        if (!class_exists($classPath)) {
            // Can not find a job for this webhook type
            abort(500, "Missing webhook job: {$classPath}");
        }

        // Dispatch
        $shopDomain = request()->header('x-shopify-shop-domain');
        $data = json_decode(request()->getContent());
        dispatch(new $classPath($shopDomain, $data));

        return response('', 201);
    }

    /**
     * Converts type into a class string
     *
     * @param string $type The type of webhook
     *
     * @return string
     */
    protected function getJobClassFromType($type)
    {
        return '\\App\\Jobs\\' . str_replace('-', '', ucwords($type, '-')) . 'Job';
    }
}
