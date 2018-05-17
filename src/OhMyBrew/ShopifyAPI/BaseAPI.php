<?php

namespace OhMyBrew\ShopifyAPI;

use Closure;
use Exception;
use GuzzleHttp\Client;

/**
 * BaseAPI is a common environment for REST/GraphQL.
 */
abstract class BaseAPI
{
    /**
     * The Guzzle client.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * The Shopify domain.
     *
     * @var string
     */
    protected $shop;

    /**
     * The Shopify access token.
     *
     * @var string
     */
    protected $accessToken;

    /**
     * The Shopify API key.
     *
     * @var string
     */
    protected $apiKey;

    /**
     * The Shopify API password.
     *
     * @var string
     */
    protected $apiPassword;

    /**
     * The Shopify API secret.
     *
     * @var string
     */
    protected $apiSecret;

    /**
     * If API calls are from a public or private app.
     *
     * @var string
     */
    protected $isPrivate;

    /**
     * The current API call limits from last request.
     *
     * @var array
     */
    protected $apiCallLimits;

    /**
     * Constructor.
     *
     * @param bool $private If this is a private or public app
     *
     * @return self
     */
    public function __construct(bool $private = false)
    {
        // Set if app is private or public
        $this->isPrivate = $private;

        // Create a default Guzzle client
        $this->client = new Client();

        // Create default placeholder
        $this->apiCallLimits = [];

        return $this;
    }

    /**
     * Sets the Guzzle client for the API calls (allows for override with your own).
     *
     * @param \GuzzleHttp\Client $client The Guzzle client
     *
     * @return self
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Sets the Shopify domain (*.myshopify.com) we're working with.
     *
     * @param string $shop The myshopify domain
     *
     * @return self
     */
    public function setShop(string $shop)
    {
        $this->shop = $shop;

        return $this;
    }

    /**
     * Gets the Shopify domain (*.myshopify.com) we're working with.
     *
     * @return string
     */
    public function getShop()
    {
        return $this->shop;
    }

    /**
     * Sets the access token for use with the Shopify API (public apps).
     *
     * @param string $accessToken The access token
     *
     * @return self
     */
    public function setAccessToken(string $accessToken)
    {
        $this->accessToken = $accessToken;

        return $this;
    }

    /**
     * Gets the access token.
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Sets the API key for use with the Shopify API (public or private apps).
     *
     * @param string $apiKey The API key
     *
     * @return self
     */
    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Sets the API secret for use with the Shopify API (public apps).
     *
     * @param string $apiSecret The API secret key
     *
     * @return self
     */
    public function setApiSecret(string $apiSecret)
    {
        $this->apiSecret = $apiSecret;

        return $this;
    }

    /**
     * Sets the API password for use with the Shopify API (private apps).
     *
     * @param string $apiPassword The API password
     *
     * @return self
     */
    public function setApiPassword(string $apiPassword)
    {
        $this->apiPassword = $apiPassword;

        return $this;
    }

    /**
     * Simple quick method to set shop and access token in one shot.
     *
     * @param string $shop        The shop's domain
     * @param string $accessToken The access token for API requests
     *
     * @return self
     */
    public function setSession(string $shop, string $accessToken)
    {
        $this->setShop($shop);
        $this->setAccessToken($accessToken);

        return $this;
    }

    /**
     * Accepts a closure to do isolated API calls for a shop.
     *
     * @param string  $shop        The shop's domain
     * @param string  $accessToken The access token for API requests
     * @param Closure $closure     The closure to run isolated
     *
     * @throws \Exception When closure is missing or not callable
     *
     * @return self
     */
    public function withSession(string $shop, string $accessToken, Closure $closure)
    {
        // Clone the API class and bind it to the closure
        $clonedApi = clone $this;
        $clonedApi->setSession($shop, $accessToken);

        return $closure->call($clonedApi);
    }

    /**
     * Gets the authentication URL for Shopify to allow the user to accept the app (for public apps).
     *
     * @param string|array $scopes      The API scopes as a comma seperated string or array
     * @param string       $redirectUri The valid redirect URI for after acceptance of the permissions.
     *                                  It must match the redirect_uri in your app settings.
     *
     * @return string Formatted URL
     */
    public function getAuthUrl($scopes, string $redirectUri)
    {
        if (is_array($scopes)) {
            $scopes = implode(',', $scopes);
        }

        return "{$this->getBaseUrl()}/admin/oauth/authorize?client_id={$this->apiKey}&scope={$scopes}&redirect_uri={$redirectUri}";
    }

    /**
     * Verify the request is from Shopify using the HMAC signature (for public apps).
     *
     * @param array $params The request parameters (ex. $_GET)
     *
     * @return bool If the HMAC is validated
     */
    public function verifyRequest(array $params)
    {
        // Ensure shop, timestamp, and HMAC are in the params
        if (array_key_exists('shop', $params)
            && array_key_exists('timestamp', $params)
            && array_key_exists('hmac', $params)
        ) {
            // Grab the HMAC, remove it from the params, then sort the params for hashing
            $hmac = $params['hmac'];
            unset($params['hmac']);
            ksort($params);

            // Encode and hash the params (without HMAC), add the API secret, and compare to the HMAC from params
            return $hmac === hash_hmac('sha256', urldecode(http_build_query($params)), $this->apiSecret);
        }

        // Not valid
        return false;
    }

    /**
     * Gets the access token from a "code" supplied by Shopify request after successfull authentication (for public apps).
     *
     * @param string $code The code from Shopify
     *
     * @throws \Exception When API secret is missing
     *
     * @return string The access token
     */
    public function requestAccessToken(string $code)
    {
        if ($this->apiSecret === null) {
            // We need the API Secret... getBaseUrl handles rest
            throw new Exception('API secret is missing');
        }

        // Do a JSON POST request to grab the access token
        $request = $this->client->request(
            'POST',
            "{$this->getBaseUrl()}/admin/oauth/access_token",
            [
                'json' => [
                    'client_id'     => $this->apiKey,
                    'client_secret' => $this->apiSecret,
                    'code'          => $code,
                ],
            ]
        );

        // Decode the response body as an array and return access token string
        return json_decode($request->getBody(), true)['access_token'];
    }
}
