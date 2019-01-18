<?php

namespace OhMyBrew;

use Closure;
use Exception;
use GuzzleHttp\Client;

/**
 * Basic Shopify API for REST & GraphQL.
 */
class BasicShopifyAPI
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
    protected $private;

    /**
     * The current API call limits from last request.
     *
     * @var array
     */
    protected $apiCallLimits = [
        'rest'  => [
            'left'  => 0,
            'made'  => 0,
            'limit' => 40,
        ],
        'graph' => [
            'left'          => 0,
            'made'          => 0,
            'limit'         => 1000,
            'restoreRate'   => 50,
            'requestedCost' => 0,
            'actualCost'    => 0,
        ],
    ];

    /**
     * If rate limiting is enabled.
     *
     * @var bool
     */
    protected $rateLimitingEnabled = false;

    /**
     * The rate limiting cycle (in ms).
     *
     * @var int
     */
    protected $rateLimitCycle = 0.5 * 1000;

    /**
     * The rate limiting cycle buffer (in ms).
     *
     * @var int
     */
    protected $rateLimitCycleBuffer = 0.1 * 1000;

    /**
     * Request timestamp for every new call.
     * Used for rate limiting.
     *
     * @var int
     */
    protected $requestTimestamp;

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
        $this->private = $private;

        // Create a default Guzzle client
        $this->client = new Client();

        return $this;
    }

    /**
     * Determines if the calls are private.
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->private === true;
    }

    /**
     * Determines if the calls are public.
     *
     * @return bool
     */
    public function isPublic()
    {
        return !$this->isPrivate();
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
     * Set the rate limiting state to enabled.
     *
     * @param int|null $cycle  The rate limiting cycle (in ms, default 500ms).
     * @param int|null $buffer The rate limiting cycle buffer (in ms, default 100ms).
     *
     * @return self
     */
    public function enableRateLimiting(int $cycle = null, int $buffer = null)
    {
        $this->rateLimitingEnabled = true;

        if (!is_null($cycle)) {
            $this->rateLimitCycle = $cycle;
        }

        if (!is_null($cycle)) {
            $this->rateLimitCycleBuffer = $buffer;
        }

        return $this;
    }

    /**
     * Set the rate limiting state to disabled.
     *
     * @return self
     */
    public function disableRateLimiting()
    {
        $this->rateLimitingEnabled = false;

        return $this;
    }

    /**
     * Determines if rate limiting is enabled.
     *
     * @return bool
     */
    public function isRateLimitingEnabled()
    {
        return $this->rateLimitingEnabled === true;
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
        if ($this->shop === null) {
            throw new Exception('Shopify domain missing for API calls');
        }

        if ($this->apiKey === null) {
            throw new Exception('API key is missing');
        }

        if (is_array($scopes)) {
            $scopes = implode(',', $scopes);
        }

        return "https://{$this->shop}/admin/oauth/authorize?client_id={$this->apiKey}&scope={$scopes}&redirect_uri={$redirectUri}";
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
        if ($this->apiSecret === null) {
            // Secret is required
            throw new Exception('API secret is missing');
        }

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
        if ($this->shop === null) {
            // Shop is required
            throw new Exception('Shopify domain missing for API calls');
        }

        if ($this->apiSecret === null || $this->apiKey === null) {
            // Key and secret required
            throw new Exception('API key or secret is missing');
        }

        // Do a JSON POST request to grab the access token
        $request = $this->client->request(
            'POST',
            "https://{$this->shop}/admin/oauth/access_token",
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

    /**
     * Alias for REST method for backwards compatibility.
     *
     * @see rest
     */
    public function request()
    {
        return call_user_func_array([$this, 'rest'], func_get_args());
    }

    /**
     * Returns the current API call limits.
     *
     * @param string|null $key The key to grab (left, made, limit, etc)
     *
     * @throws \Exception When attempting to grab a key that doesn't exist
     *
     * @return array An array of the Guzzle response, and JSON-decoded body
     */
    public function getApiCalls(string $type = 'rest', string $key = null)
    {
        if ($key) {
            $keys = array_keys($this->apiCallLimits[$type]);
            if (!in_array($key, $keys)) {
                // No key like that in array
                throw new Exception('Invalid API call limit key. Valid keys are: '.implode(', ', $keys));
            }

            // Return the key value requested
            return $this->apiCallLimits[$type][$key];
        }

        // Return all the values
        return $this->apiCallLimits[$type];
    }

    /**
     * Runs a request to the Shopify API.
     *
     * @param string $query     The GraphQL query
     * @param array  $variables The optional variables for the query
     *
     * @throws \Exception When missing api password is missing for private apps
     * @throws \Exception When missing access key is missing for public apps
     *
     * @return array An array of the Guzzle response, and JSON-decoded body
     */
    public function graph(string $query, array $variables = [])
    {
        if ($this->shop === null) {
            // Shop is requiured
            throw new Exception('Shopify domain missing for API calls');
        }

        if ($this->private && ($this->apiPassword === null && $this->accessToken === null)) {
            // Private apps need password for use as access token
            throw new Exception('API password/access token required for private Shopify GraphQL calls');
        } elseif (!$this->private && $this->accessToken === null) {
            // Need access token for public calls
            throw new Exception('Access token required for public Shopify GraphQL calls');
        }

        // Build the request
        $request = ['query' => $query];
        if (count($variables) > 0) {
            $request['variables'] = $variables;
        }

        // Update the timestamp of the request
        $tmpTimestamp = $this->requestTimestamp;
        $this->requestTimestamp = microtime(true);

        // Create the request, pass the access token and optional parameters
        $response = $this->client->request(
            'POST',
            "https://{$this->shop}/admin/api/graphql.json",
            [
                'headers' => [
                    'X-Shopify-Access-Token' => $this->apiPassword ?? $this->accessToken,
                    'Content-Type'           => 'application/json',
                ],
                'body'    => json_encode($request),
            ]
        );

        // Grab the data result and extensions
        $body = $this->jsonDecode($response->getBody());
        if (property_exists($body, 'extensions') && property_exists($body->extensions, 'cost')) {
            // Update the API call information
            $calls = $body->extensions->cost;
            $this->apiCallLimits['graph'] = [
                'left'          => (int) $calls->throttleStatus->currentlyAvailable,
                'made'          => (int) ($calls->throttleStatus->maximumAvailable - $calls->throttleStatus->currentlyAvailable),
                'limit'         => (int) $calls->throttleStatus->maximumAvailable,
                'restoreRate'   => (int) $calls->throttleStatus->restoreRate,
                'requestedCost' => (int) $calls->requestedQueryCost,
                'actualCost'    => (int) $calls->actualQueryCost,
            ];
        }

        // Return Guzzle response and JSON-decoded body
        return (object) [
            'response'   => $response,
            'body'       => property_exists($body, 'errors') ? $body->errors : $body->data,
            'errors'     => property_exists($body, 'errors'),
            'timestamps' => [$tmpTimestamp, $this->requestTimestamp],
        ];
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
    public function rest(string $type, string $path, array $params = null)
    {
        if ($this->shop === null) {
            // Shop is required
            throw new Exception('Shopify domain missing for API calls');
        }

        if ($this->private && ($this->apiKey === null || $this->apiPassword === null)) {
            // Key and password are required for private API calls
            throw new Exception('API key and password required for private Shopify REST calls');
        }

        // Build the request parameters for Guzzle
        $guzzleParams = [];
        $guzzleParams[strtoupper($type) === 'GET' ? 'query' : 'json'] = $params;
        if (!$this->private) {
            $guzzleParams['headers'] = ['X-Shopify-Access-Token' => $this->accessToken];
        }

        // Create the request, pass the access token and optional parameters
        if ($this->private) {
            $uri = "https://{$this->apiKey}:{$this->apiPassword}@{$this->shop}{$path}";
        } else {
            $uri = "https://{$this->shop}{$path}";
        }

        // Check the rate limit before firing the request
        if ($this->isRateLimitingEnabled() && $this->requestTimestamp) {
            // Calculate in milliseconds the duration the API call took
            $duration = round(microtime(true) - $this->requestTimestamp, 3) * 1000;
            $waitTime = ($this->rateLimitCycle - $duration) + $this->rateLimitCycleBuffer;

            if ($waitTime > 0) {
                // Do the sleep for X mircoseconds (convert from milliseconds)
                usleep($waitTime * 1000);
            }
        }

        // Update the timestamp of the request
        $tmpTimestamp = $this->requestTimestamp;
        $this->requestTimestamp = microtime(true);

        $response = $this->client->request($type, $uri, $guzzleParams);

        // Grab the API call limit header returned from Shopify
        $callLimitHeader = $response->getHeader('http_x_shopify_shop_api_call_limit');
        if ($callLimitHeader) {
            $calls = explode('/', $callLimitHeader[0]);
            $this->apiCallLimits['rest'] = [
                'left'  => (int) $calls[1] - $calls[0],
                'made'  => (int) $calls[0],
                'limit' => (int) $calls[1],
            ];
        }

        // Return Guzzle response and JSON-decoded body
        return (object) [
            'response'   => $response,
            'body'       => $this->jsonDecode($response->getBody()),
            'timestamps' => [$tmpTimestamp, $this->requestTimestamp],
        ];
    }

    /**
     * Decodes the JSON body.
     *
     * @param string $json The JSON body
     *
     * @return object The decoded JSON
     */
    protected function jsonDecode($json)
    {
        // From firebase/php-jwt
        if (!(defined('JSON_C_VERSION') && PHP_INT_SIZE > 4)) {
            /**
             * In PHP >=5.4.0, json_decode() accepts an options parameter, that allows you
             * to specify that large ints (like Steam Transaction IDs) should be treated as
             * strings, rather than the PHP default behaviour of converting them to floats.
             */
            $obj = json_decode($json, false, 512, JSON_BIGINT_AS_STRING);
        } else {
            // @codeCoverageIgnoreStart
            /**
             * Not all servers will support that, however, so for older versions we must
             * manually detect large ints in the JSON string and quote them (thus converting
             * them to strings) before decoding, hence the preg_replace() call.
             * Currently not sure how to test this so I ignored it for now.
             */
            $maxIntLength = strlen((string) PHP_INT_MAX) - 1;
            $jsonWithoutBigints = preg_replace('/:\s*(-?\d{'.$maxIntLength.',})/', ': "$1"', $json);
            $obj = json_decode($jsonWithoutBigints);
            // @codeCoverageIgnoreEnd
        }

        return $obj;
    }
}
