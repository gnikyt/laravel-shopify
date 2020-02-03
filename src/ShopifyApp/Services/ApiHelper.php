<?php

namespace OhMyBrew\ShopifyApp\Services;

use Closure;
use OhMyBrew\BasicShopifyAPI;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Exception\RequestException;
use OhMyBrew\ShopifyApp\Exceptions\ApiException;
use OhMyBrew\ShopifyApp\Objects\Enums\ApiMethod;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeId;

/**
 * Basic helper class for API calls to Shopify.
 */
class ApiHelper implements IApiHelper
{
    /**
     * The API instance.
     *
     * @var BasicShopifyAPI
     */
    protected $api;

    /**
     * {@inheritDoc}
     */
    public function setInstance(BasicShopifyAPI $api): ApiHelper
    {
        $this->api = $api;

        return $this;
    }

    /**
     * Run a one-time function with an API instance.
     * The instance state is reset to the previous state.
     *
     * @param BasicShopifyAPI $api The API instance.
     * @param Closure         $fn  The function to call.
     *
     * @return void
     */
    public function withInstance(BasicShopifyAPI $api, Closure $fn): void
    {
        // Save current instance
        $currentApi = $this->api;

        // Run the function, inject the temporary instance
        $this->api = $api;
        $fn($this);

        // Put the previous instance back
        $this->api = $currentApi;
    }

    /**
     * {@inheritDoc}
     */
    public function buildAuthUrl(string $mode, string $scopes): string
    {
        return $this->api->getAuthUrl(
            $scopes,
            URL::secure(Config::get('shopify-app.api_redirect')),
            $mode
        );
    }

    /**
     * {@inheritDoc}
     */
    public function verifyRequest(array $request): bool
    {
        return $this->api->verifyRequest($request);
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessData(string $code)
    {
        return $this->api->requestAccess($code);
    }

    /**
     * {@inheritDoc}
     */
    public function getScriptTags(array $params = []): array
    {
        // Setup the params
        $reqParams = array_merge(
            [
                'limit'  => 250,
                'fields' => 'id,src',
            ],
            $params
        );

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::GET()->toNative(),
            '/admin/script_tags.json',
            $reqParams
        );

        return $response->body->script_tags;
    }

    /**
     * {@inheritDoc}
     */
    public function createScriptTag(array $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST()->toNative(),
            '/admin/script_tags.json',
            ['script_tag' => $payload]
        );

        return $response->body;
    }

    /**
     * {@inheritDoc}
     */
    public function getCharge(string $chargeType, ChargeId $chargeId): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::GET()->toNative(),
            "/admin/{$chargeType}/{$chargeId->toNative()}.json"
        );

        return $response->body->{substr($chargeType, 0, -1)};
    }

    /**
     * {@inheritDoc}
     */
    public function activateCharge(string $chargeType, ChargeId $chargeId): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST()->toNative(),
            "/admin/{$chargeType}/{$chargeId->toNative()}/activate.json"
        );

        return $response->body->{substr($chargeType, 0, -1)};
    }

    /**
     * {@inheritDoc}
     */
    public function createCharge(string $chargeType, PlanDetailsTransfer $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST()->toNative(),
            "/admin/{$chargeType}.json",
            ['charge' => (array) $payload]
        );

        return $response->body->{substr($chargeType, 0, -1)};
    }

    /**
     * {@inheritDoc}
     */
    public function getWebhooks(array $params = []): array
    {
        // Setup the params
        $reqParams = array_merge(
            [
                'limit'  => 250,
                'fields' => 'id,address',
            ],
            $params
        );

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::GET()->toNative(),
            '/admin/webhooks.json',
            $reqParams
        );

        return $response->body->webhooks;
    }

    /**
     * {@inheritDoc}
     */
    public function createWebhook(array $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST()->toNative(),
            '/admin/webhooks.json',
            ['webhook' => $payload]
        );

        return $response->body->webhook;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteWebhook(int $webhookId): void
    {
        // Fire the request
        $this->doRequest(
            ApiMethod::DELETE()->toNative(),
            "/admin/webhooks/{$webhookId}.json"
        );
    }

    /**
     * {@inheritDoc}
     */
    public function createUsageCharge(UsageChargeDetailsTransfer $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST()->toNative(),
            "/admin/recurring_application_charges/{$payload->chargeId}/usage_charges.json",
            [
                'usage_charge' => [
                    'price'       => $payload->price,
                    'description' => $payload->description,
                ],
            ]
        );
            
        return $response->body->usage_charge;
    }

    /**
     * Fire the request using the API instance.
     *
     * @param string $method  The HTTP method.
     * @param string $path    The endpoint path.
     * @param array  $payload The optional payload to send to the endpoint.
     *
     * @return object|RequestException
     */
    protected function doRequest(string $method, string $path, array $payload = null)
    {
        $response = $this->api->rest($method, $path, $payload);
        if (!$response || $response->errors === true) {
            // Request error somewhere, throw the exception
            throw new ApiException($response->exception);
        }

        return $response;
    }
}
