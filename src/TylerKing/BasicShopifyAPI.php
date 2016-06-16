<?php
namespace TylerKing;

use GuzzleHttp\Client;
use \Exception;

/**
 * BasicShopifyAPI is a simple wrapper for Shopify API
 */
class BasicShopifyAPI {
  /** @var \GuzzleHttp\Client The Guzzle client */
  protected $client;

  /** @var string The Shopify domain */
  protected $shop;

  /** @var string The Shopify access token */
  protected $access_token;

  /** @var string The Shopify API key */
  protected $api_key;

  /** @var string The Shopify API password */
  protected $api_password;

  /** @var string The Shopify API secret */
  protected $api_secret;

  /** @var boolean If API calls are public or private */
  protected $is_private;

  /** @var array The current API call limits from last request */
  protected $api_call_limits;

  /**
   * Constructor.
   *
   * @param boolean $private If this is a private or public app
   *
   * @return self
   */
  public function __construct($private = false) {
    // Set if app is private or public
    $this->is_private      = $private;

    // Create a default Guzzle client
    $this->client          = new Client;

    // Create default placeholders for call limits
    $this->api_call_limits = ['left' => 0, 'made' => 0, 'limit' => 40];

    return $this;
  }

  /**
   * Sets the Guzzle client for the API calls (allows for override with your own).
   *
   * @param \GuzzleHttp\Client $client
   *
   * @return self
   */
  public function setClient(Client $client) {
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
  public function setShop($shop) {
    $this->shop = $shop;

    return $this;
  }

  /**
   * Gets the Shopify domain (*.myshopify.com) we're working with.
   *
   * @return string
   */
  public function getShop() {
    return $this->shop;
  }

  /**
   * Sets the access token for use with the Shopify API (public apps).
   *
   * @param string $access_token The access token
   * @return self
   */
  public function setAccessToken($access_token) {
    $this->access_token = $access_token;

    return $this;
  }

  /**
   * Sets the API key for use with the Shopify API (public or private apps).
   *
   * @param string $api_key The API key
   *
   * @return self
   */
  public function setApiKey($api_key) {
    $this->api_key = $api_key;

    return $this;
  }

  /**
   * Sets the API secret for use with the Shopify API (public apps).
   *
   * @param string $api_secret The API secret key
   *
   * @return self
   */
  public function setApiSecret($api_secret) {
    $this->api_secret = $api_secret;

    return $this;
  }

  /**
   * Sets the API password for use with the Shopify API (private apps).
   *
   * @param string $api_password The API password
   *
   * @return self
   */
  public function setApiPassword($api_password) {
    $this->api_password = $api_password;

    return $this;
  }

  /**
   * Gets the authentication URL for Shopify to allow the user to accept the app (for public apps).
   *
   * @param string|array $scopes The API scopes as a comma seperated string or array
   * @param string $redirect_uri The valid redirect URI for after acceptance of the permissions.
   *                             It must match the redirect_uri in your app settings.
   * @return string Formatted URL
   */
  public function getAuthUrl($scopes, $redirect_uri) {
    if (is_array($scopes)) {
      $scopes = implode(',', $scopes);
    }

    return "{$this->getBaseUrl()}/admin/oauth/authorize?client_id={$this->api_key}&scope={$scopes}&redirect_uri={$redirect_uri}";
  }

  /**
   * Verify the request is from Shopify using the HMAC signature (for public apps).
   *
   * @param array $params The request parameters (ex. $_GET)
   *
   * @return boolean If the HMAC is validated
   */
  public function verifyRequest($params) {
    if (!is_array($params)) {
      // No params, this is not valid
      return false;
    }

    // Ensure shop, timestamp, and HMAC are in the params
    if (array_key_exists('shop', $params) && array_key_exists('timestamp', $params) && array_key_exists('hmac', $params)) {
      // Grab the HMAC, remove it from the params, then sort the params for hashing
      $hmac = $params['hmac'];
      unset($params['hmac']);
      ksort($params);

      // Encode and hash the params (without HMAC), add the API secret, and compare to the HMAC from params
      return $hmac === hash_hmac('sha256', urldecode(http_build_query($params)), $this->api_secret);
    }

    // Missing a required param
    return false;
  }

  /**
   * Gets the access token from a "code" supplied by Shopify request after successfull authentication (for public apps).
   *
   * @param string $code The code from Shopify
   *
   * @return string The access token
   *
   * @throws \Exception When API secret is missing
   */
  public function getAccessToken($code) {
    if ($this->api_secret === null) {
      // We need the API Secret... getBaseUrl handles rest
      throw new Exception('API secret is missing');
    }

    // Do a JSON POST request to grab the access token
    $request = $this->client->request(
      'POST',
      "{$this->getBaseUrl()}/admin/oauth/access_token",
      ['json' => ['client_id' => $this->api_key, 'client_secret' => $this->api_secret, 'code' => $code]]
    );

    // Decode the response body as an array and return access token string
    return json_decode($request->getBody(), true)['access_token'];
  }

  /**
   * Runs a request to the Shopify API
   *
   * @param string $type The type of request... GET, POST, PUT, DELETE
   * @param string $path The Shopify API path... /admin/xxxx/xxxx.json
   * @param array|null $params Optional parameters to send with the request
   *
   * @return array An array of the Guzzle response, and JSON-decoded body
   */
  public function request($type, $path, $params = []) {
    // Create the request, pass the access token and optional parameters
    $response = $this->client->request(
      $type,
      $this->getBaseUrl().$path,
      [
        'headers' => ['X-Shopify-Access-Token' => $this->access_token],
        'json'    => $params
      ]
    );

    // Grab the API call limit header returned from Shopify
    $calls       = explode('/', $response->getHeader('http_x_shopify_shop_api_call_limit')[0]);
    $calls_made  = $calls[0];
    $calls_limit = $calls[1];

    // Set it into the class
    $this->api_call_limits = [
      'left'  => (int) $calls_limit - $calls_made,
      'made'  => (int) $calls_made,
      'limit' => (int) $calls_limit
    ];

    // Return Guzzle response and JSON-decoded body
    return (object) [
      'response' => $response,
      'body'     => json_decode($response->getBody())
    ];
  }

  /**
   * Returns the current API call limits
   *
   * @param string|null $key The key to grab (left, made, limit)
   *
   * @return array An array of the Guzzle response, and JSON-decoded body
   *
   * @throws \Exception When attempting to grab a key that doesn't exist
   */
  public function getApiCalls($key = null) {
    if ($key) {
      if (! in_array($key, ['left', 'made', 'limit'])) {
        // No key like that in array
        throw new Exception('Invalid API call limit key. Valid keys are: '.implode(', ', array_keys($this->api_call_limits)));
      }

      // Return the key value requested
      return $this->api_call_limits[$key];
    }

    // Return all the values
    return $this->api_call_limits;
  }

  /**
   * Gets the base URL to use depending on if its a privte or public app
   *
   * @return string The final base URL to use with the API
   *
   * @throws \Exception When missing API key or API password for private apps
   * @throws \Exception When missing Shopify domain
   */
  protected function getBaseUrl() {
    if ($this->is_private && ($this->api_key === null || $this->api_password === null)) {
      // Private apps need key and password
      throw new Exception('API key and password required for private Shopify API calls');
    }

    if ($this->shop === null) {
      // Public and private apps need domain regardless
      throw new Exception('Shopify domain missing for API calls');
    }

    return $this->is_private ? "https://{$this->api_key}:{$this->api_password}@{$this->shop}" : "https://{$this->shop}";
  }
}
