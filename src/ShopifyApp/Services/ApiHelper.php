<?php

namespace OhMyBrew\ShopifyApp\Services;

use OhMyBrew\BasicShopifyAPI;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use GuzzleHttp\Exception\RequestException;
use OhMyBrew\ShopifyApp\DTO\PlanDetailsDTO;

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
            self::METHOD_GET,
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
            self::METHOD_POST,
            '/admin/script_tags.json',
            ['script_tag' => $payload]
        );

        return $response->body;
    }

    /**
     * {@inheritDoc}
     */
    public function getCharge(string $chargeType, int $chargeId): object
    {
        // Fire the request
        $response = $this->doRequest(
            self::METHOD_GET,
            "/admin/{$chargeType}/{$chargeId}.json"
        );

        return $response->body->{substr($chargeType, 0, -1)};
    }

    /**
     * {@inheritDoc}
     */
    public function activateCharge(string $chargeType, int $chargeId): object
    {
        // Fire the request
        $response = $this->doRequest(
            self::METHOD_POST,
            "/admin/{$chargeType}/{$chargeId}/activate.json"
        );

        return $response->body->{substr($chargeType, 0, -1)};
    }

    /**
     * {@inheritDoc}
     */
    public function createCharge(string $chargeType, PlanDetailsDTO $payload): object
    {
        // Fire the request
        $response = $this->doRequest(
            self::METHOD_POST,
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
            self::METHOD_GET,
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
            self::METHOD_POST,
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
            self::METHOD_DELETE,
            "/admin/webhooks/{$webhookId}.json"
        );
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
        if ($response->errors === true) {
            // Request error somewhere, throw the exception
            throw $response->exception;
        }

        return $response;
    }
}
