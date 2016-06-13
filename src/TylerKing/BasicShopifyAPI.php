<?php
namespace TylerKing;

use GuzzleHttp\Client;
use \Exception;

class BasicShopifyAPI {
  private $client;
  private $shop;
  private $access_token;
  private $api_key;
  private $api_password;
  private $api_secret;
  private $is_private;
  private $api_call_limits;

  public function __construct($private = false) {
    $this->is_private      = $private;
    $this->client          = new Client;
    $this->api_call_limits = ['left' => 0, 'made' => 0, 'limit' => 40];

    return $this;
  }

  public function setClient($client) {
    $this->client = $client;

    return $this;
  }

  public function setShop($shop) {
    $this->shop = $shop;

    return $this;
  }

  public function getShop() {
    return $this->shop;
  }

  public function setAccessToken($access_token) {
    $this->access_token = $access_token;

    return $this;
  }

  public function setApiKey($api_key) {
    $this->api_key = $api_key;

    return $this;
  }

  public function setApiSecret($api_secret) {
    $this->api_secret = $api_secret;

    return $this;
  }

  public function setApiPassword($api_password) {
    $this->api_password = $api_password;

    return $this;
  }

  public function getAuthUrl($scopes, $redirect_uri) {
    if (is_array($scopes)) {
      $scopes = implode(',', $scopes);
    }

    return "{$this->getBaseUrl()}/admin/oauth/authorize?client_id={$this->api_key}&scope={$scopes}&redirect_uri={$redirect_uri}";
  }

  public function verifyRequest($params) {
    if (!is_array($params)) {
      return false;
    }

    if (array_key_exists('shop', $params) && array_key_exists('timestamp', $params) && array_key_exists('hmac', $params)) {
      $hmac = $params['hmac'];
      unset($params['hmac']);
      ksort($params);

      return $hmac === hash_hmac('sha256', urldecode(http_build_query($params)), $this->api_secret);
    }

    return false;
  }

  public function getAccessToken($code) {
    if ($this->api_secret === null) {
      throw new Exception('API secret is missing');
    }

    $request = $this->client->request(
      'POST',
      "{$this->getBaseUrl()}/admin/oauth/access_token",
      ['json' => ['client_id' => $this->api_key, 'client_secret' => $this->api_secret, 'code' => $code]]
    );

    return json_decode($request->getBody(), true)['access_token'];
  }

  public function request($type, $path, $params = []) {
    $response = $this->client->request(
      $type,
      $this->getBaseUrl().$path,
      [
        'headers' => ['X-Shopify-Access-Token' => $this->access_token],
        'json'    => $params
      ]
    );

    $calls       = explode('/', $response->getHeader('http_x_shopify_shop_api_call_limit')[0]);
    $calls_made  = $calls[0];
    $calls_limit = $calls[1];

    $this->api_call_limits = [
      'left'  => (int) $calls_limit - $calls_made,
      'made'  => (int) $calls_made,
      'limit' => (int) $calls_limit
    ];

    return (object) [
      'response' => $response,
      'body'     => json_decode($response->getBody())
    ];
  }

  public function getApiCalls($key = null) {
    if ($key) {
      if (! in_array($key, ['left', 'made', 'limit'])) {
        throw new Exception('Invalid API call limit key. Valid keys are: '.implode(', ', array_keys($this->api_call_limits)));
      }

      return $this->api_call_limits[$key];
    }

    return $this->api_call_limits;
  }

  private function getBaseUrl() {
    if ($this->is_private && ($this->api_key === null || $this->api_password === null)) {
      throw new Exception('API key and password required for private Shopify API calls');
    }

    if ($this->shop === null) {
      throw new Exception('Shopify domain missing for API calls');
    }

    return $this->is_private ? "https://{$this->api_key}:{$this->api_password}@{$this->shop}" : "https://{$this->shop}";
  }
}
