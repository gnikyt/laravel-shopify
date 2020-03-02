<?php

namespace OhMyBrew\ShopifyApp\Services;

use Closure;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\URL;
use OhMyBrew\BasicShopifyAPI;
use OhMyBrew\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use OhMyBrew\ShopifyApp\Exceptions\ApiException;
use OhMyBrew\ShopifyApp\Objects\Enums\ApiMethod;
use OhMyBrew\ShopifyApp\Objects\Enums\AuthMode;
use OhMyBrew\ShopifyApp\Objects\Enums\ChargeType;
use OhMyBrew\ShopifyApp\Objects\Transfers\ApiSession as ApiSessionTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use OhMyBrew\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use OhMyBrew\ShopifyApp\Objects\Values\ChargeReference;
use OhMyBrew\ShopifyApp\Traits\ConfigAccessible;

/**
 * Basic helper class for API calls to Shopify.
 */
class ApiHelper implements IApiHelper
{
    use ConfigAccessible;

    /**
     * The API instance.
     *
     * @var BasicShopifyAPI
     */
    protected $api;

    /**
     * {@inheritdoc}
     */
    public function make(ApiSessionTransfer $session = null): self
    {
        // Create the instance
        $apiClass = $this->getConfig('api_class');
        $this->api = new $apiClass();
        $this->api
            ->setApiKey($this->getConfig('api_class'))
            ->setApiSecret($this->getConfig('api_secret'))
            ->setVersion($this->getConfig('api_version'));

        // Enable basic rate limiting?
        if ($this->getConfig('api_rate_limiting_enabled') === true) {
            $this->api->enableRateLimiting(
                $this->getConfig('api_rate_limit_cycle'),
                $this->getConfig('api_rate_limit_cycle_buffer')
            );
        }

        // Set session?
        if ($session !== null) {
            // Set the session to the shop's domain/token
            $this->api->setSession(
                $session->domain->toNative(),
                $session->token->toNative()
            );
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setApi(BasicShopifyAPI $api): self
    {
        $this->api = $api;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getApi(): BasicShopifyAPI
    {
        return $this->api;
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
    public function withApi(BasicShopifyAPI $api, Closure $fn): void
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
     * {@inheritdoc}
     */
    public function buildAuthUrl(AuthMode $mode, string $scopes): string
    {
        return $this->api->getAuthUrl(
            $scopes,
            URL::secure($this->getConfig('api_redirect')),
            strtolower($mode->toNative())
        );
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore No need to retest.
     */
    public function verifyRequest(array $request): bool
    {
        return $this->api->verifyRequest($request);
    }

    /**
     * {@inheritdoc}
     *
     * @codeCoverageIgnore No need to retest.
     */
    public function getAccessData(string $code)
    {
        return $this->api->requestAccess($code);
    }

    /**
     * {@inheritdoc}
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
            ApiMethod::GET(),
            '/admin/script_tags.json',
            $reqParams
        );

        return $response->body->script_tags;
    }

    /**
     * {@inheritdoc}
     */
    public function createScriptTag(array $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            '/admin/script_tags.json',
            ['script_tag' => $payload]
        );

        return $response->body;
    }

    /**
     * {@inheritdoc}
     */
    public function getCharge(ChargeType $chargeType, ChargeReference $chargeRef): object
    {
        // API path
        $typeString = $this->chargeApiPath($chargeType);

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::GET(),
            "/admin/{$typeString}s/{$chargeRef->toNative()}.json"
        );

        return $response->body->{$typeString};
    }

    /**
     * {@inheritdoc}
     */
    public function activateCharge(ChargeType $chargeType, ChargeReference $chargeRef): object
    {
        // API path
        $typeString = $this->chargeApiPath($chargeType);

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            "/admin/{$typeString}s/{$chargeRef->toNative()}/activate.json"
        );

        return $response->body->{$typeString};
    }

    /**
     * {@inheritdoc}
     */
    public function createCharge(ChargeType $chargeType, PlanDetailsTransfer $payload): object
    {
        // API path
        $typeString = $this->chargeApiPath($chargeType);

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            "/admin/{$typeString}s.json",
            ['charge' => (array) $payload]
        );

        return $response->body->{$typeString};
    }

    /**
     * {@inheritdoc}
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
            ApiMethod::GET(),
            '/admin/webhooks.json',
            $reqParams
        );

        return $response->body->webhooks;
    }

    /**
     * {@inheritdoc}
     */
    public function createWebhook(array $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            '/admin/webhooks.json',
            ['webhook' => $payload]
        );

        return $response->body->webhook;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWebhook(int $webhookId): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::DELETE(),
            "/admin/webhooks/{$webhookId}.json"
        );

        return $response->body;
    }

    /**
     * {@inheritdoc}
     */
    public function createUsageCharge(UsageChargeDetailsTransfer $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            "/admin/recurring_application_charges/{$payload->chargeReference->toNative()}/usage_charges.json",
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
     * Converts ChargeType enum into an API path.
     *
     * @param ChargeType $chargeType The charge type.
     *
     * @return string
     */
    protected function chargeApiPath(ChargeType $chargeType): string
    {
        // Convert to API path
        $format = $chargeType->isSame(ChargeType::RECURRING()) ?
            '%s_application_charge' :
            'application_%s';

        return sprintf($format, strtolower($chargeType->toNative()));
    }

    /**
     * Fire the request using the API instance.
     *
     * @param ApiMode $method  The HTTP method.
     * @param string  $path    The endpoint path.
     * @param array   $payload The optional payload to send to the endpoint.
     *
     * @return object|RequestException
     */
    protected function doRequest(ApiMethod $method, string $path, array $payload = null)
    {
        $response = $this->api->rest($method->toNative(), $path, $payload);
        if (property_exists($response, 'errors') && $response->errors === true) {
            // Request error somewhere, throw the exception
            throw new ApiException(
                is_string($response->body) ? $response->body : 'Unknown error',
                0,
                $response->exception
            );
        }

        return $response;
    }
}
