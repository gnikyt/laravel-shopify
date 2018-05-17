<?php

namespace OhMyBrew\ShopifyAPI;

use Exception;

/**
 * GraphQL Shopify API.
 */
class GraphAPI extends BaseAPI
{
    /**
     * Constructor.
     *
     * @param bool $private If this is a private or public app
     *
     * @return self
     */
    public function __construct(bool $private = false)
    {
        parent::__construct($private);

        // Create default placeholder
        $this->apiCallLimits = [
            'left'          => 0,
            'made'          => 0,
            'limit'         => 1000,
            'restoreRate'   => 50,
            'requestedCost' => 0,
            'actualCost'    => 0,
        ];

        return $this;
    }

    /**
     * Runs a request to the Shopify API.
     *
     * @param string $query The GraphQL query
     *
     * @throws \Exception When missing api password is missing for private apps
     * @throws \Exception When missing access key is missing for public apps
     *
     * @return array An array of the Guzzle response, and JSON-decoded body
     */
    public function request(string $query)
    {
        if ($this->isPrivate && $this->apiPassword === null) {
            // Private apps need password for use as access token
            throw new Exception('API password required for Shopify GraphQL calls');
        } elseif (!$this->isPrivate && $this->accessToken === null) {
            // Need access token for public calls
            throw new Exception('Access token required for Shopify GraphQL calls');
        }

        // Create the request, pass the access token and optional parameters
        $response = $this->client->request(
            'POST',
            "{$this->getBaseUrl()}/admin/api/graphql.json",
            [
                'headers' => [
                    'X-Shopify-Access-Token' => $this->apiPassword ?? $this->accessToken,
                    'Content-Type'           => 'application/graphql',
                ],
                'body'    => $query,
            ]
        );

        // From firebase/php-jwt
        $body = $response->getBody();
        if (!(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /**
             * In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $bodyObj = json_decode($body, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            // @codeCoverageIgnoreStart
            /**
             * Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             * them to strings) before decoding, hence the preg_replace() call.
             * Currently not sure how to test this so I ignored it for now.
             */
            $maxIntLength = strlen((string) PHP_INT_MAX) - 1;
            $jsonWithoutBigints = preg_replace('/:\s*(-?\d{'.$maxIntLength.',})/', ': "$1"', $body);
            $bodyObj = json_decode($jsonWithoutBigints);
            // @codeCoverageIgnoreEnd
        }

        // Grab the data result and extensions
        $data = $bodyObj->data;
        $calls = $bodyObj->extensions->cost;

        // Update the API call information
        $this->apiCallLimits = [
            'left'          => (int) $calls->throttleStatus->currentlyAvailable,
            'made'          => (int) ($calls->throttleStatus->maximumAvailable - $calls->throttleStatus->currentlyAvailable),
            'limit'         => (int) $calls->throttleStatus->maximumAvailable,
            'restoreRate'   => (int) $calls->throttleStatus->restoreRate,
            'requestedCost' => (int) $calls->requestedQueryCost,
            'actualCost'    => (int) $calls->actualQueryCost,
        ];

        // Return Guzzle response and JSON-decoded body
        return (object) [
            'response' => $response,
            'body'     => $data,
        ];
    }

    /**
     * Gets the base URL to use.
     *
     * @throws \Exception When missing Shopify domain
     *
     * @return string The final base URL to use with the API
     */
    public function getBaseUrl() : string
    {
        if ($this->shop === null) {
            // Public and private apps need domain regardless
            throw new Exception('Shopify domain missing for API calls');
        }

        return "https://{$this->shop}";
    }
}
