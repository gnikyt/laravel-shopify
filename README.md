# Basic Shopify API

[![Build Status](https://travis-ci.org/ohmybrew/Basic-Shopify-API.svg?branch=master)](http://travis-ci.org/ohmybrew/Basic-Shopify-API)
[![Coverage Status](https://coveralls.io/repos/github/ohmybrew/Basic-Shopify-API/badge.svg?branch=master)](https://coveralls.io/github/ohmybrew/Basic-Shopify-API?branch=master)
[![StyleCI](https://styleci.io/repos/61004776/shield?branch=master)](https://styleci.io/repos/61004776)
[![License](https://poser.pugx.org/ohmybrew/basic-shopify-api/license)](https://packagist.org/packages/ohmybrew/basic-shopify-api)

A simple, tested, API wrapper for Shopify using Guzzle. It supports both the REST and GraphQL API provided by Shopify, and basic rate limiting abilities. It contains helpful methods for generating a installation URL, an authorize URL (offline and per-user), HMAC signature validation, call limits, and API requests. It works with both OAuth and private API apps.

Also supported: asynchronous requests through Guzzle's promises.

This library required PHP >= 7.

## Table of Contents
  * [Installation](#installation)
  * [Usage](#usage)
      * [Public API](#public-api)
        * [REST (sync)](#rest-sync)
        * [REST (async)](#rest-async)
        * [GraphQL](#graphql)
        * [Getting access (offline)](#getting-access-offline)
        * [Getting access (per-user)](#getting-access-per-user)
        * [Verifying HMAC signature](#verifying-hmac-signature)
      * [Private API](#private-api)
        * [REST](#rest)
        * [GraphQL](#graphql-1)
      * [Making requests](#making-requests)
        * [REST](#rest-1)
            * [If sync is true (regular rest call):](#if-sync-is-true-regular-rest-call)
            * [If sync is false (restAsync call):](#if-sync-is-false-restasync-call)
        * [GraphQL](#graphql-2)
      * [API Versioning](#api-versioning)
      * [Checking API limits](#checking-api-limits)
      * [Rate Limiting](#rate-limiting)
        * [Enable Rate Limiting](#enable-rate-limiting)
        * [Disabiling Rate Limiting](#disabiling-rate-limiting)
        * [Checking Rate Limiting Status](#checking-rate-limiting-status)
        * [page_info / pagination Support](#page_info--pagination-support)
        * [Getting Timestamps](#getting-timestamps)
      * [Isolated API calls](#isolated-api-calls)
      * [Errors](#errors)
      * [Logging](#logging)
  * [Documentation](#documentation)
  * [LICENSE](#license)

## Installation

The recommended way to install is [through composer](http://packagist.org).

    $ composer require ohmybrew/basic-shopify-api

## Usage

Add `use OhMyBrew\BasicShopifyAPI;` to your imports.

### Public API

This assumes you properly have your app setup in the partner's dashboard with the correct keys and redirect URIs.

#### REST (sync)

For REST calls, the shop domain and access token are required.

```php
$api = new BasicShopifyAPI();
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
$api->setShop('your shop here');
$api->setAccessToken('your token here');

// Now run your requests...
$resul = $api->rest(...);
```

#### REST (async)

For REST calls, the shop domain and access token are required.

```php
$api = new BasicShopifyAPI();
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
$api->setShop('your shop here');
$api->setAccessToken('your token here');

// Now run your requests...
$promise = $api->restAsync(...);
$promise->then(function ($result) {
  // ...
});
```

#### GraphQL

For GraphQL calls, the shop domain and access token are required.

```php
$api = new BasicShopifyAPI();
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
$api->setShop('your shop here');
$api->setAccessToken('your token here');

// Now run your requests...
$api->graph(...);
```

#### Getting access (offline)

This is the default mode which returns a permanent token.
 
After obtaining the user's shop domain, to then direct them to the auth screen use `getAuthUrl`, as example (basic PHP):

```php
$api = new BasicShopifyAPI();
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
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
  $api->requestAndSetAccess($code);

  // Above is equiv. to:
  //
  // $access = $api->requestAccess($code);
  // $api->setAccessToken($access->access_token);
  //
  // You can use: $api->getAccessToken() and set it into the database or a cookie, etc

  // You can now make API callsn`
  $request = $api->rest('GET', '/admin/shop.json'); // or GraphQL
}
```

#### Getting access (per-user)

You can also change the grant mode to be `per-user` as [outlined in Shopify documentation](https://help.shopify.com/en/api/getting-started/authentication/oauth/api-access-modes). This will receieve user info from the user of the app within the Shopify store. The token recieved will expire at a specific time.

```php
$api = new BasicShopifyAPI();
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
$api->setShop($_SESSION['shop']);
$api->setApiKey(env('SHOPIFY_API_KEY'));
$api->setApiSecret(env('SHOPIFY_API_SECRET'));

$code = $_GET['code'];
if (!$code) {
  /**
   * No code, send user to authorize screen
   * Pass your scopes as an array for the first argument
   * Pass your redirect URI as the second argument
   * Pass your grant mode as the third argument
   */
  $redirect = $api->getAuthUrl(env('SHOPIFY_API_SCOPES'), env('SHOPIFY_API_REDIRECT_URI'), 'per-user');
  header("Location: {$redirect}");
  exit;
} else {
  // We now have a code, lets grab the access object
  $api->requestAndSetAccess($code);

  // Above is equiv. to:
  //
  // $access = $api->requestAccess($code);
  // $api->setAccessToken($access->access_token);
  // $api->setUser($access->associated_user)
  //
  // You can use: $api->getAccessToken() and set it into a cookie, etc
  // You can also get user details with: $api->getUser(), example: $api->getUser()->email

  // You can now make API calls
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

For REST (sync) calls, shop domain, API key, and API password are request

```php
$api = new BasicShopifyAPI(true); // true sets it to private
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
$api->setShop('example.myshopify.com');
$api->setApiKey('your key here');
$api->setApiPassword('your password here');

// Now run your requests...
$result = $api->rest(...);
```

#### GraphQL

For GraphQL calls, shop domain and API password are required.

```php
$api = new BasicShopifyAPI(true); // true sets it to private
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
$api->setShop('example.myshopify.com');
$api->setApiPassword('your password here');

// Now run your requests...
$api->graph(...);
```

### Making requests

#### REST

Requests are made using Guzzle.

```php
$api->rest(string $type, string $path, array $params = null, array $headers = [], bool $sync = true);
```

+ `type` refers to GET, POST, PUT, DELETE, etc
+ `path` refers to the API path, example: `/admin/products/1920902.json`
+ `params` refers to an array of params you wish to pass to the path, examples: `['handle' => 'cool-coat']`
+ `headers` refers to an array of custom headers you would like to optionally send with the request, example: `['X-Shopify-Test' => '123']`
+ `sync` refers to if the request should be synchronous or asynchronous.

You can use the alias `restAsync` to skip setting `sync` to `false`.

##### If sync is true (regular rest call):

The return value for the request will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body

*Note*: `request()` will alias to `rest()` as well.

##### If sync is false (restAsync call):

The return value for the request will be a Guzzle promise which you can handle on your own.

The return value for the promise will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body

```php
$promise = $api->restAsync(...);
$promise->then(function ($result) {
  // `response` and `body` available in `$result`.
});
```

#### GraphQL

Requests are made using Guzzle.

```php
$api->graph(string $query, array $variables = []);
```

+ `query` refers to the full GraphQL query
+ `variables` refers to the variables used for the query (if any)

The return value for the request will be an object containing:

+ `response` the full Guzzle response object
+ `body` the JSON decoded response body
+ `errors` if there was errors or not

Example query:

```php
$result = $api->graph('{ shop { productz(first: 1) { edges { node { handle, id } } } } }');
echo $result->body->shop->products->edges[0]->node->handle; // test-product
```

Example mutation:

```php
$result = $api->graph(
    'mutation collectionCreate($input: CollectionInput!) { collectionCreate(input: $input) { userErrors { field message } collection { id } } }',
    ['input' => ['title' => 'Test Collection']]
);
echo $result->body->collectionCreate->collection->id; // gid://shopify/Collection/63171592234
```

### API Versioning

This library supports [versioning the requests](https://www.shopify.com/partners/blog/api-versioning-at-shopify), example:

```php
$api = new BasicShopifyAPI(true);
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"

// ... your code
```

You can override the versioning at anytime for specific API requests, example:

```php
$api = new BasicShopifyAPI(true);
$api->setVersion('2019-04');

$api->rest('GET', '/admin/api/unstable/shop.json'); // Will ignore "2019-04" version and use "unstable" for this request
// ... your code
```

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

### Rate Limiting

This library comes with a built-in basic rate limiter, disabled by default. It will sleep for *x* microseconds to ensure you do not go over the limit for calls with Shopify. On non-Plus plans, you get 1 call every 500ms (2 calls a second), for Plus plans you get 2 calls every 500ms (4 calls a second).

By default the cycle is set to 500ms, with a buffer for safety of 100ms added on.

#### Enable Rate Limiting

Setup your API instance as normal, with an added:

```php
$api->enableRateLimiting();
```

This will turn on rate limiting with the default 500ms cycle and 100ms buffer. To change this, do the following:

```php
$api->enableRateLimiting(0.25 * 1000, 0);
```

This will set the cycle to 250ms and 0ms buffer.

#### Disabiling Rate Limiting

If you've previously enabled it, you simply need to run:

```php
$api->disableRateLimiting();
```

#### Checking Rate Limiting Status

```php
$api->isRateLimitingEnabled();
```

#### page_info / pagination Support

2019-07 API version introduced a new `Link` header which is used for pagination ([explained here](https://help.shopify.com/en/api/guides/paginated-rest-results)).

If an endpoint supports page_info, you can use `$response->link` to grab the page_info value to pass in your next request.

Example:

```php
$response = $api->rest('GET', '/admin/products.json', ['limit' => 5]);
$link = $response->link->next; // eyJsYXN0X2lkIjo0MDkw
$link2 = $response->link->previous; // dkUIsk00wlskWKl
$response = $api->rest('GET', '/admin/products.json', ['limit' => 5, 'page_info' => $link]);
```

#### Getting Timestamps

The library will track timestamps from the previous and current (last) call. To see information on this:

```php
$response = $api->rest('POST', '/admin/gift_cards.json', ['gift_cards' => ['initial_value' => 25.00]]);
print_r($response->timestamps);

/* Above will return an array of [previous call, current (last) call], example:
 * [1541119962.965, 1541119963.3121] */
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
$api->setVersion('2019-04'); // "YYYY-MM" or "unstable"
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

### Errors

This library internally catches only 400-500 status range errors through Guzzle. You're able to check for an error of this type and get its response status code and body.

```php
$call = $api->rest('GET', '/admin/non-existant-route-or-object.json');

if ($call->errors) {
  echo "Oops! {$call->status} error";
  var_dump($call->body);

  // Original exception can be accessed via `$call->exception`
  // Example, if response body was `{"error": "Not found"}`...
  /// then: `$call->body` would return "Not Found"
}
```

### Logging

This library accepts a PSR-compatible logger.

```php
$api->setLogger(... your logger instance ...);
```

## Documentation

Code documentation is [available here](https://ohmybrew.com/Basic-Shopify-API) from phpDocumentor via `phpdoc -d src -t doc`.

## LICENSE

This project is released under the MIT [license](https://github.com/ohmybrew/Basic-Shopify-API/blob/master/LICENSE).
