<?php

namespace OhMyBrew\ShopifyAPI;

use Exception;

/**
 * REST Shopify API.
 */
class RestAPI extends BaseAPI
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
        $this->apiCallLimits = ['left' => 0, 'made' => 0, 'limit' => 40];

        return $this;
    }

    /**
     * Runs a request to the Shopify API.
     *
     * @param string     $type   The type of request... GET, POST, PUT, DELETE
     * @param string     $path   The Shopify API path... /admin/xxxx/xxxx.json
     * @param array|null $params Optional parameters to send with the request
     *
     * @return array An array of the Guzzle response, and JSON-decoded body
     */
    public function request(string $type, string $path, array $params = null)
    {
        // Build the request parameters for Guzzle
        $guzzleParams = ['headers' => ['X-Shopify-Access-Token' => $this->accessToken]];
        $guzzleParams[strtoupper($type) === 'GET' ? 'query' : 'json'] = $params;

        // Create the request, pass the access token and optional parameters
        $response = $this->client->request(
            $type,
            $this->getBaseUrl().$path,
            $guzzleParams
        );

        // Grab the API call limit header returned from Shopify
        $calls = explode('/', $response->getHeader('http_x_shopify_shop_api_call_limit')[0]);
        $callsMade = $calls[0];
        $callsLimit = $calls[1];

        // Set it into the class
        $this->apiCallLimits = [
            'left'  => (int) $callsLimit - $callsMade,
            'made'  => (int) $callsMade,
            'limit' => (int) $callsLimit,
        ];

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

        // Return Guzzle response and JSON-decoded body
        return (object) [
            'response' => $response,
            'body'     => $bodyObj,
        ];
    }

    /**
     * Gets the base URL to use depending on if its a privte or public app.
     *
     * @throws \Exception When missing API key or API password for private apps
     * @throws \Exception When missing Shopify domain
     *
     * @return string The final base URL to use with the API
     */
    public function getBaseUrl() : string
    {
        if ($this->isPrivate && ($this->apiKey === null || $this->apiPassword === null)) {
            // Private apps need key and password
            throw new Exception('API key and password required for private Shopify API calls');
        }

        if ($this->shop === null) {
            // Public and private apps need domain regardless
            throw new Exception('Shopify domain missing for API calls');
        }

        return $this->isPrivate ? "https://{$this->apiKey}:{$this->apiPassword}@{$this->shop}" : "https://{$this->shop}";
    }
}
