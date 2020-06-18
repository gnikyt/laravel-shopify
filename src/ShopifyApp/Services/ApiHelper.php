<?php

namespace Osiset\ShopifyApp\Services;

use Closure;
use stdClass;
use Illuminate\Support\Facades\URL;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\Session;
use GuzzleHttp\Exception\RequestException;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Exceptions\ApiException;
use Osiset\ShopifyApp\Objects\Enums\ApiMethod;
use Osiset\ShopifyApp\Traits\ConfigAccessible;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;

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
    public function make(Session $session = null): self
    {
        // Create the options
        $opts = new Options();
        $opts->setApiKey($this->getConfig('api_key'));
        $opts->setApiSecret($this->getConfig('api_secret'));
        $opts->setVersion($this->getConfig('api_version'));

        // Create the instance
        if ($this->getConfig('api_init')) {
            // User-defined init function
            $this->api = call_user_func($this->getConfig('api_init'), $opts);
        } else {
            // Default init
            $ts = $this->getConfig('api_time_store');
            $ls = $this->getConfig('api_limit_store');
            $sd = $this->getConfig('api_deferrer');

            $this->api = new BasicShopifyAPI(
                $opts,
                new $ts(),
                new $ls(),
                new $sd()
            );
        }

        // Set session?
        if ($session !== null) {
            // Set the session to the shop's domain/token
            $this->api->setSession($session);
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
        // Fix for peruser => per-user
        $mode = $mode->isSame(AuthMode::PERUSER()) ? 'PER-USER' : $mode->toNative();

        return $this->api->getAuthUrl(
            $scopes,
            URL::secure($this->getConfig('api_redirect')),
            strtolower($mode)
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
    public function getAccessData(string $code): ResponseAccess
    {
        return $this->api->requestAccess($code);
    }

    /**
     * {@inheritdoc}
     */
    public function getScriptTags(array $params = []): ResponseAccess
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

        return $response['body']['script_tags'];
    }

    /**
     * {@inheritdoc}
     */
    public function createScriptTag(array $payload): ResponseAccess
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            '/admin/script_tags.json',
            ['script_tag' => $payload]
        );

        return $response['body'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCharge(ChargeType $chargeType, ChargeReference $chargeRef): ResponseAccess
    {
        // API path
        $typeString = $this->chargeApiPath($chargeType);

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::GET(),
            "/admin/{$typeString}s/{$chargeRef->toNative()}.json"
        );

        return $response['body'][$typeString];
    }

    /**
     * {@inheritdoc}
     */
    public function activateCharge(ChargeType $chargeType, ChargeReference $chargeRef): ResponseAccess
    {
        // API path
        $typeString = $this->chargeApiPath($chargeType);

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            "/admin/{$typeString}s/{$chargeRef->toNative()}/activate.json"
        );

        return $response['body'][$typeString];
    }

    /**
     * {@inheritdoc}
     */
    public function createCharge(ChargeType $chargeType, PlanDetailsTransfer $payload): ResponseAccess
    {
        // API path
        $typeString = $this->chargeApiPath($chargeType);

        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            "/admin/{$typeString}s.json",
            [$typeString => $payload->toArray()]
        );

        return $response['body'][$typeString];
    }

    /**
     * {@inheritdoc}
     */
    public function getWebhooks(array $params = []): ResponseAccess
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

        return $response['body']['webhooks'];
    }

    /**
     * {@inheritdoc}
     */
    public function createWebhook(array $payload): ResponseAccess
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            '/admin/webhooks.json',
            ['webhook' => $payload]
        );

        return $response['body']['webhook'];
    }

    /**
     * {@inheritdoc}
     */
    public function deleteWebhook(int $webhookId): ResponseAccess
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::DELETE(),
            "/admin/webhooks/{$webhookId}.json"
        );

        return $response['body'];
    }

    /**
     * {@inheritdoc}
     */
    public function createUsageCharge(UsageChargeDetailsTransfer $payload)
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

        return !empty($response['body']['usage_charge']) ?
            $response['body']['usage_charge'] :
            false;
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
        if ($chargeType->isSame(ChargeType::RECURRING())) {
            $format =  '%s_application_charge';
        } elseif ($chargeType->isSame(ChargeType::CHARGE())) {
            $format = 'application_charge';
        } else {
            $format = 'application_%s';
        }

        return sprintf($format, strtolower($chargeType->toNative()));
    }

    /**
     * Fire the request using the API instance.
     *
     * @param ApiMode $method  The HTTP method.
     * @param string  $path    The endpoint path.
     * @param array   $payload The optional payload to send to the endpoint.
     *
     * @throws RequestException
     *
     * @return array
     */
    protected function doRequest(ApiMethod $method, string $path, array $payload = null)
    {
        $response = $this->api->rest($method->toNative(), $path, $payload);
        if ($response['errors'] === true) {
            // Request error somewhere, throw the exception
            throw new ApiException(
                is_string($response['body']) ? $response['body'] : 'Unknown error',
                0,
                $response['exception']
            );
        }

        return $response;
    }
}
