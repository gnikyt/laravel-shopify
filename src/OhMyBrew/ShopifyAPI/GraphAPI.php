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

        // Build the request parameters for Guzzle
        $guzzleParams = [
            'headers' => [
                'X-Shopify-Access-Token' => $this->apiPassword || $this->accessToken,
                'Content-Type'           => 'application/graphql',
            ],
        ];

        // Create the request, pass the access token and optional parameters
        $response = $this->client->request(
            $type,
            $this->getBaseUrl(),
            $guzzleParams
        );
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

        return "https://{$this->shop}/admin/api/graphql.json";
    }
}
