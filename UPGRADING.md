# Upgrading

# v4.x.x -> v5.x.x

No upgrading is required if you do not capture 400-500 exceptions from Guzzle yourself. If you do, the library now handles these exceptions internally and returns them inside the resulting object.

# v1.x.x -> v3.0.0

+ `getApiCalls()` now takes two arguments, first being rest|graph, second being the key

Old:

```php
getApiCalls('left');
```

New:

```php
getApiCalls('rest', 'left');
```

+ `request()` still exists, and is aliased to `rest()` but encourage you to move all REST calls to the new `rest()` method name