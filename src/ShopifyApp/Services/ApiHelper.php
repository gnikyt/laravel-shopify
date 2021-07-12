<?php

namespace Osiset\ShopifyApp\Services;

use Closure;
use Exception;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;
use Osiset\BasicShopifyAPI\BasicShopifyAPI;
use Osiset\BasicShopifyAPI\Options;
use Osiset\BasicShopifyAPI\ResponseAccess;
use Osiset\BasicShopifyAPI\Session;
use Osiset\ShopifyApp\Contracts\ApiHelper as IApiHelper;
use Osiset\ShopifyApp\Exceptions\ApiException;
use Osiset\ShopifyApp\Objects\Enums\ApiMethod;
use Osiset\ShopifyApp\Objects\Enums\AuthMode;
use Osiset\ShopifyApp\Objects\Enums\ChargeType;
use Osiset\ShopifyApp\Objects\Enums\DataSource;
use Osiset\ShopifyApp\Objects\Transfers\PlanDetails as PlanDetailsTransfer;
use Osiset\ShopifyApp\Objects\Transfers\UsageChargeDetails as UsageChargeDetailsTransfer;
use Osiset\ShopifyApp\Objects\Values\ChargeReference;
use Osiset\ShopifyApp\Objects\Values\NullableShopDomain;
use Osiset\ShopifyApp\Util;

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
     * {@inheritdoc}
     */
    public function make(Session $session = null): self
    {
        // Create the options
        $opts = new Options();

        $shop = $this->getShopDomain($session)->toNative();
        $opts->setApiKey(Util::getShopifyConfig('api_key', $shop));
        $opts->setApiSecret(Util::getShopifyConfig('api_secret', $shop));
        $opts->setVersion(Util::getShopifyConfig('api_version', $shop));

        // Create the instance
        if (Util::getShopifyConfig('api_init')) {
            // User-defined init function
            $this->api = call_user_func(
                Util::getShopifyConfig('api_init'),
                $opts,
                $session,
                Request::all()
            );
        } else {
            // Default init
            $ts = Util::getShopifyConfig('api_time_store', $shop);
            $ls = Util::getShopifyConfig('api_limit_store', $shop);
            $sd = Util::getShopifyConfig('api_deferrer', $shop);

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
            URL::secure(Util::getShopifyConfig('api_redirect')),
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
     * TODO: Convert to GraphQL.
     */
    public function getScriptTags(array $params = []): ResponseAccess
    {
        // Setup the params
        $reqParams = array_merge(
            [
                'limit' => 250,
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
     * TODO: Convert to GraphQL.
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
     * TODO: Convert to GraphQL.
     */
    public function deleteScriptTag(int $scriptTagId): ResponseAccess
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::DELETE(),
            "/admin/script_tags/{$scriptTagId}.json"
        );

        return $response['body'];
    }

    /**
     * {@inheritdoc}
     * TODO: Convert to GraphQL.
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
     * TODO: Convert to GraphQL.
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
     * TODO: Convert to GraphQL (merge createChargeGraphQL).
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
     *
     * @throws Exception
     */
    public function createChargeGraphQL(PlanDetailsTransfer $payload): ResponseAccess
    {
        $query = '
        mutation appSubscriptionCreate(
            $name: String!,
            $returnUrl: URL!,
            $trialDays: Int,
            $test: Boolean,
            $lineItems: [AppSubscriptionLineItemInput!]!
        ) {
            appSubscriptionCreate(
                name: $name,
                returnUrl: $returnUrl,
                trialDays: $trialDays,
                test: $test,
                lineItems: $lineItems
            ) {
                appSubscription {
                    id
                }
                confirmationUrl
                userErrors {
                    field
                    message
                }
            }
        }
        ';
        $variables = [
            'name' => $payload->name,
            'returnUrl' => $payload->returnUrl,
            'trialDays' => $payload->trialDays,
            'test' => $payload->test,
            'lineItems' => [
                [
                    'plan' => [
                        'appRecurringPricingDetails' => [
                            'price' => [
                                'amount' => $payload->price,
                                'currencyCode' => 'USD',
                            ],
                            'interval' => $payload->interval,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->doRequestGraphQL($query, $variables);

        return $response['body']['data']['appSubscriptionCreate'];
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function getWebhooks(array $params = []): ResponseAccess
    {
        $query = '
        query webhookSubscriptions($first: Int!) {
            webhookSubscriptions(first: $first) {
                edges {
                    node {
                        id
                        topic
                        endpoint {
                            ...on WebhookHttpEndpoint {
                                callbackUrl
                            }
                        }
                    }
                }
            }
        }
        ';

        $variables = [
            'first' => 250,
        ];

        $response = $this->doRequestGraphQL($query, $variables);

        return $response['body'];
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function createWebhook(array $payload): ResponseAccess
    {
        $query = '
        mutation webhookSubscriptionCreate(
            $topic: WebhookSubscriptionTopic!,
            $webhookSubscription: WebhookSubscriptionInput!
        ) {
            webhookSubscriptionCreate(
                topic: $topic
                webhookSubscription: $webhookSubscription
            ) {
                userErrors {
                    field
                    message
                }
                webhookSubscription {
                    id
                    topic
                }
            }
        }
        ';

        // Change REST-format topics ("resource/event")
        // to GraphQL-format topics ("RESOURCE_EVENT"), for pre-v17 compatibility
        $topic = Util::getGraphQLWebhookTopic($payload['topic']);
        $variables = [
            'topic' => $topic,
            'webhookSubscription' => [
                'callbackUrl' => $payload['address'],
                'format' => 'JSON',
            ],
        ];

        $response = $this->doRequestGraphQL($query, $variables);

        return $response['body'];
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception
     */
    public function deleteWebhook(string $webhookId): ResponseAccess
    {
        $query = '
        mutation webhookSubscriptionDelete($id: ID!) {
            webhookSubscriptionDelete(id: $id) {
                userErrors {
                    field
                    message
                }
                deletedWebhookSubscriptionId
            }
        }
        ';

        $variables = [
            'id' => $webhookId,
        ];

        $response = $this->doRequestGraphQL($query, $variables);

        return $response['body'];
    }

    /**
     * {@inheritdoc}
     * TODO: Convert to GraphQL.
     */
    public function createUsageCharge(UsageChargeDetailsTransfer $payload)
    {
        // Fire the request
        $response = $this->doRequest(
            ApiMethod::POST(),
            "/admin/recurring_application_charges/{$payload->chargeReference->toNative()}/usage_charges.json",
            [
                'usage_charge' => [
                    'price' => $payload->price,
                    'description' => $payload->description,
                ],
            ]
        );

        return isset($response['body']) && isset($response['body']['usage_charge'])
            ? $response['body']['usage_charge'] : false;
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
            $format = '%s_application_charge';
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
     * @param ApiMethod $method  The HTTP method.
     * @param string    $path    The endpoint path.
     * @param ?array    $payload The optional payload to send to the endpoint.
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

    /**
     * Fire the request using the GraphQL API Instance.
     *
     * @param string $query   The query of GraphQL
     * @param array  $payload The option payload to using on the query
     *
     * @throws Exception
     *
     * @return array
     */
    protected function doRequestGraphQL(string $query, array $payload = []): array
    {
        $response = $this->api->graph($query, $payload);
        if ($response['errors'] !== false) {
            $message = is_array($response['errors'])
                ? $response['errors'][0]['message'] : $response['errors'];

            // Request error somewhere, throw the exception
            throw new Exception($message);
        }

        return $response;
    }

    /**
     * Find shop from session or request.
     *
     * @param Session|null $session
     *
     * @return NullableShopDomain
     */
    private function getShopDomain(Session $session = null): NullableShopDomain
    {
        // Check for existing session passed in
        if ($session && $session->getShop()) {
            return NullableShopDomain::fromNative($session->getShop());
        }

        $options = [
            // Input
            DataSource::INPUT()->toNative() => function (): ?string {
                return Arr::get(Request::all(), 'shop');
            },
            // Headers
            DataSource::HEADER()->toNative() => function (): ?string {
                return Request::header('X-Shop-Domain');
            },
            // Headers: Referer
            DataSource::REFERER()->toNative() => function (): ?string {
                $url = parse_url(Request::server('HTTP_REFERER'), PHP_URL_QUERY);
                parse_str($url, $refererQueryParams);

                return Arr::get($refererQueryParams, 'shop');
            },
        ];
        foreach ($options as $fn) {
            $result = $fn();
            if ($result !== null) {
                return NullableShopDomain::fromNative($result);
            }
        }

        return NullableShopDomain::fromNative(null);
    }
}
