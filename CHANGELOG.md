# CHANGELOG

# 6.0.0

+ Added ability to do Guzzle sync and async requests through Promises.
+ `errors` now returns a boolean instead of an object. `body` now contains the error response.

# 5.5.0

+ Added ability to use custom headers in REST calls.

# 5.4.0

+ Added option to supply a PSR-compatible logger via `setLogger`.

# 5.3.3

+ Added matching to ignore certain oauth paths.

# 5.3.2

+ Added for for issue #24 in detection of what an authable request is.

# 5.3.1

+ Fixed merged in to prevent versioning on access token calls.

# 5.3.0

+ Added support for versioned API calls for both REST and GraphAPI
+ New `setVersion(string $version)` method added, and new `getVersion()` method added

# 5.2.0

+ Added ability for `per-user` authentication. There is now ability to set/grab a user from authentication.
+ `requestAndSetAccess(string $code)` method added which will automatically set the access token and user (if applicable) to the API instance for you as a helper method.

# 5.1.0

+ Added a `authRequest` middleware to Guzzle which handles adding all needed headers and checking required API keys to run those requests
+ Fixed issue for redirect not working

# 5.0.0

*Possible breaking release depending on how you handle errors*

+ 400-500 errors are now captured internally and accessible through the resulting object (#16)
+ Middleware was added to the Guzzle requests to fix redirections (#16)

# 4.0.2

+ Changes to the response of GraphQL calls to better check for errors.

# 4.0.1

+ Update for more accurate timing

# 4.0.0

+ Added rate limiting abilities (basic)

# 3.0.3

+ Fix for #13 for requests where call limit header is not always supplied

# 3.0.2

+ Adjusted API to work better with Shopify's implementation of GraphQL (#10)
+ `graph()` call now accepts two arguments, `graph(string $query, array $variables = [])`

# Vesion 3.0.1

+ Fix to obtaining access token

# Version 3.0.0

*Contains breaking changes*

To better the library, it has been reverted back to its original single-class form and backwards compatibile with 1.x.x

+ GraphQL and REST are all under one class
+ `getApiCalls()` now takes two arguments, first being rest|graph, second being the key
+ `rest()` is now for REST calls
+ `graph()` is now for GraphQL calls
+ `request()` is aliased to `rest()` for backward compatibility

# Version 2.0.0

*Contains breaking changes*

+ No longer a single file, it now namespaced under `OhmyBrew\ShopifyAPI`
+ GraphQL is now introduced under `OhMyBrew\ShopifyAPI\GraphAPI`
+ REST is moved to `OhMyBrew\ShopifyAPI\RestAPI`

# Version 1.0.1

+ Fixed issue #3 by @ncpope, for newly created stores (after December 2017) not accepting GET body

# Version 1.0.0

+ Basic API code implemented
+ Tests completed with full coverage