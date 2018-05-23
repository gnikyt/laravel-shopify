# Basic Shopify API

[![Build Status](https://travis-ci.org/ohmybrew/Basic-Shopify-API.svg?branch=master)](http://travis-ci.org/ohmybrew/Basic-Shopify-API)
[![Coverage Status](https://coveralls.io/repos/github/ohmybrew/Basic-Shopify-API/badge.svg?branch=master)](https://coveralls.io/github/ohmybrew/Basic-Shopify-API?branch=master)
[![StyleCI](https://styleci.io/repos/61004776/shield?branch=master)](https://styleci.io/repos/61004776)
[![License](https://poser.pugx.org/ohmybrew/basic-shopify-api/license)](https://packagist.org/packages/ohmybrew/basic-shopify-api)

A simple, tested, API wrapper for Shopify using Guzzle. It supports both the REST and GraphQL API provided by Shopify. It contains helpful methods for generating a installation URL, an authorize URL, HMAC signature validation, call limits, and API requests. It works with both OAuth and private API apps.

This library required PHP >= 7.

## Installation

The recommended way to install is [through composer](http://packagist.org).

    $ composer require ohmybrew/basic-shopify-api

## Usage

Add `use OhMyBrew\BasicShopifyAPI;` to your imports.

### Public API

This assumes you properly have your app setup in the partner's dashboard with the correct keys and redirect URIs.

#### REST

For REST calls, the shop domain and access token are required.

```php
$api = new BasicShopifyAPI();
$api->setShop('your shop here');
$api->setAccessToken('your token here');

// Now run your requests...
$api->rest(...);
```

#### GraphQL

For GraphQL calls, the shop domain and access token are required.

```php
$api = new BasicShopifyAPI();
$api->setShop('your shop here');
$api->setAccessToken('your token here');

// Now run your requests...
$api->graph(...);
```

#### Getting access token

After obtaining the user's shop domain, to then direct them to the auth screen use `getAuthUrl`, as example (basic PHP):

```php
$api = new BasicShopifyAPI();
$api->setShop($_SESSION['shop']);
$api->setApiKey(env('SHOPIFY_API_KEY'));
$api->setApiSecret(env('SHOPIFY_API_SECRET'));

$code = $_GET['code'];
if (!$code) {
  /**
   * No code, send user to authorize screen
   * Pass your scopes as an array for the first argument
   * Pass your redirect URI as the second argument
   */
  $redirect = $api->getAuthUrl(env('SHOPIFY_API_SCOPES'), env('SHOPIFY_API_REDIRECT_URI'));
  header("Location: {$redirect}");
  exit;
} else {
  // We now have a code, lets grab the access token
  $token = $api->requestAccessToken($code);

  // You can now do what you wish with the access token after this (store it to db, etc)
  $api->setAccessToken($token);

  // You can now make API calls as well once you've set the token to `setAccessToken`
  $request = $api->rest('GET', '/admin/shop.json'); // or GraphQL
}
```

#### Verifying HMAC signature

Simply pass in an array of GET params.

```php
// Will return true or false if HMAC signature is good.
$valid = $api->verifyRequest($_GET);
```

### Private API

This assumes you properly have your app setup in the partner's dashboard with the correct keys and redirect URIs.

#### REST

For REST calls, shop domain, API key, and API password are request

```php
$api = new BasicShopifyAPI(true); // true sets it to private
$api->setShop('example.myshopify.com');
$api->setApiKey('your key here');
$api->setApiPassword('your password here');

// Now run your requests...
$api->rest(...);
```

#### GraphQL

For GraphQL calls, shop domain and API password are required.

```php
$api = new BasicShopifyAPI(true); // true sets it to private
$api->setShop('example.myshopify.com');
$api->setApiPassword('your password here');

// Now run your requests...
$api->graph(...);
```

### Making requests

#### REST

Requests are made using Guzzle.

```php
$api->rest(string $type, string $path, array $params = null);
```

+ `type` refers to GET, POST, PUT, DELETE, etc
+ `path` refers to the API path, example: `/admin/products/1920902.json`
+ `params` refers to an array of params you wish to pass to the path, examples: `['handle' => 'cool-coat']`

The return value for the request will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body

*Note*: `request()` will alias to `rest()` as well.

#### GraphQL

Requests are made using Guzzle.

```php
$api->graph(string $query);
```

+ `query` refers to the full GraphQL query

The return value for the request will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body

### Checking API limits

After each request is made, the API call limits are updated. To access them, simply use:

```php
// Returns an array of left, made, and limit.
// Example: ['left' => 79, 'made' => 1, 'limit' => 80]
$limits = $api->getApiCalls('rest'); // or 'graph'
```

For GraphQL, additionally there will be the following values: `restoreRate`, `requestedCost`, `actualCost`.

To quickly get a value, you may pass an optional parameter to the `getApiCalls` method:

```php
// As example, this will return 79
// You may pass 'left', 'made', or 'limit'
$left = $api->getApiCalls('graph', 'left'); // returns 79
// or
$left = $api->getApiCalls('graph')['left']; // returns 79
```

### Isolated API calls

You can initialize the API once and use it for multiple shops. Each instance will be contained to not pollute the others. This is useful for something like background job processing.

```php
$api->withSession(string $shop, string $accessToken, Closure $closure);
```

+ `shop` refers to the Shopify domain
+ `accessToken` refers to the access token for the API calls
+ `closure` refers to the closure to call for the session

`$this` will be binded to the current API. Example:

```php
$api = new BasicShopifyAPI(true);
$api->setApiKey('your key here');
$api->setApiPassword('your password here');

$api->withSession('some-shop.myshopify.com', 'token from database?', function() {
  $request = $this->rest('GET', '/admin/shop.json');
  echo $request->body->shop->name; // Some Shop
});

$api->withSession('some-shop-two.myshopify.com', 'token from database?', function() {
  $request = $this->rest('GET', '/admin/shop.json');
  echo $request->body->shop->name; // Some Shop Two
});
```

## Documentation

Code documentation is [available here](https://ohmybrew.com/Basic-Shopify-API) from phpDocumentor via `phpdoc -d src -t doc`.

## LICENSE

This project is released under the MIT [license](https://github.com/ohmybrew/Basic-Shopify-API/blob/master/LICENSE).
