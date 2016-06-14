# Basic Shopify API

A simple, tested, API wrapper for Shopify using Guzzle. I created this to support my legacy apps. It contains helpful methods for generating a installation URL, an authorize URL, HMAC signature validation, call limits, and API requests. It works with both OAuth and private API apps.

[![Build Status](https://travis-ci.org/tyler-king/Basic-Shopify-API.svg?branch=master)](http://travis-ci.org/tyler-king/Basic-Shopify-API)

## Installation

The recommended way to install is [through composer](http://packagist.org).

    $ composer require tyler-king/basic-shopify-api

## Usage

*Warning: This section needs, and will be, expanded on.*

### Public API

For OAuth applications. The shop domain, API key, API secret, and an access token are required. This assumes you properly have your app setup in the partner's dashboard with the correct keys and redirect URIs.

#### Quick run-down

```php
use TylerKing\BasicShopifyAPI;

$api = new BasicShopifyAPI;
$api->setShop('example.myshopify.com');
$api->setApiKey('your key here');
$api->setApiSecret('your secret here');
$api->setAccessToken('a token here');

# $request will return an object with keys of `response` for full Guzzle response, and `body` with JSON-decoded result
$request = $api->request('GET', '/admin/shop.json');
print $request->response->getStatusCode();
print $request->body->shop->name;
```

#### Getting access token

After obtaining the user's shop domain, to then direct them to the auth screen use `getAuthUrl`, as example:

```php
$api = new BasicShopifyAPI;
$api->setShop($app['session']->get('shop'));
$api->setApiKey($app['config']->shopify_api_key);

$code = $request->query->get('code');
if (! $code) {
  # No code, send user to authorize screen
  # Pass your scopes as an array for the first argument
  # Pass your redirect URI as the second argument
  header('Location: ' . $api->getAuthUrl($app['config']->shopify_scopes, $app['config']->shopify_redirect_uri));
  exit;
} else {
  # We now have a code, lets grab the access token
  $token = $api->getAccessToken($code);

  # You can now do what you wish with the access token after this (store it to db, etc)
  $api->setAccessToken($token);

  # You can now make API calls as well once you've set the token to `setAccessToken`
  $request = $api->request('GET', '/admin/shop.json');
}
```

#### Verifying HMAC signature

Simply pass in an array of GET params.

```php
# Will return true or false if HMAC signature is good.
$valid = $api->verifyRequest($request->query->all());
```

### Private API

For private application calls. The shop domain, API key, and API password are required.

#### Quick run-down

```php
$api = new BasicShopifyAPI(true); // true sets it to private
$api->setShop('example.myshopify.com');
$api->setApiKey('your key here');
$api->setApiPassword('your password here');

# $request will return an object with keys of `response` for full Guzzle response, and `body` with JSON-decoded result
$request = $api->request('GET', '/admin/shop.json');
print $request->response->getStatusCode();
print $request->body->shop->name;
```

### Making requests

Requests are made using Guzzle.

```php
$api->request($type, $path, $params = []);
```

+ `type` refers to GET, POST, PUT, DELETE, etc
+ `path` refers to the API path, example: `/admin/products/1920902.json`
+ `params` refers to an array of params you wish to pass to the path, examples: `['handle' => 'cool-coat']`

The return value for the request will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body

### Checking API limits

After each request is made, the API call limits are updated. To access them, simply use:

```php
# Returns an array of left, made, and limit.
# Example: ['left' => 79, 'made' => 1, 'limit' => 80]
$limits = $api->getApiCalls();
```

To quickly get a value, you may pass an optional parameter to the `getApiCalls` method:

```php
# As example, this will return 79
# You may pass 'left', 'made', or 'limit'
$left = $api->getApiCalls('left'); // returns 79
```
