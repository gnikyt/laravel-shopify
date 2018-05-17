# Upgrading

# v1.x.x -> v2.0.0

Library is no longer a single class. It now has a namespace with several classes.

Old:

```php
use OhMyBrew\BasicShopifyAPI;

$api = new BasicShopifyAPI(...);
```

New:

```php

use OhMyBrew\ShopifyAPI;

$api = new RestAPI(...);
```

A GraphQL API class is now also included, use `GraphAPI`.
