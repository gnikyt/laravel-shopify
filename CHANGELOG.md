# CHANGELOG

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