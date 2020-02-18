# Laravel Shopify App

![Tests](https://github.com/ohmybrew/laravel-shopify/workflows/Package%20Test/badge.svg?branch=structure-change)
[![Coverage](https://coveralls.io/repos/github/ohmybrew/laravel-shopify/badge.svg?branch=structure-change)](https://coveralls.io/github/ohmybrew/laravel-shopify?branch=structure-change)
[![License](https://poser.pugx.org/ohmybrew/laravel-shopify/license)](https://packagist.org/packages/ohmybrew/laravel-shopify)

**Note: (structure-change) Is a complete library rewrite. The package has grown significantly, and with it has come challenges in maintaining. The goal of this branch is to seperate logic into bits, confirm to SOLID, CQR, DRY, etc. where applicable.**

**This rewrite introduces the use of using the user model provided by Laravel instead of its own shop model. Laravel's IoC will be used everywhere it can, and more logic moved into small services and action classes.**

**The current status of the rewrite is completed and most of the testing is completed. Currently testing the last bit which is the controller traits and middlewares.**

----

A full-featured Laravel package for aiding in Shopify App development, similar to `shopify_app` for Rails. Works for Laravel 5.6+

![Screenshot](https://github.com/ohmybrew/laravel-shopify/raw/master/screenshot.png)
![Screenshot: Billable](https://github.com/ohmybrew/laravel-shopify/raw/master/screenshot-billable.png)

## Table of Contents

__*__ *Wiki pages*

- [Goals](#goals)
- [Documentation](#documentation)
- [Requirements](https://github.com/ohmybrew/laravel-shopify/wiki/Requirements)*
- [Installation](https://github.com/ohmybrew/laravel-shopify/wiki/Installation)*  *(New video guide to come soon)*
- [Route List](https://github.com/ohmybrew/laravel-shopify/wiki/Route-List)*
- [Usage](https://github.com/ohmybrew/laravel-shopify/wiki/Usage)*
- [Changelog](https://github.com/ohmybrew/laravel-shopify/wiki/Changelog)*
- [Roadmap](https://github.com/ohmybrew/laravel-shopify/wiki/Roadmap)*
- [Contributing Guide](https://github.com/ohmybrew/laravel-shopify/blob/master/CONTRIBUTING.md)
- [LICENSE](#license)

For more information, tutorials, etc., please view the project's [wiki](https://github.com/ohmybrew/laravel-shopify/wiki).

## Goals

- [x] Provide assistance in developing Shopify apps with Laravel
- [x] Integration with Shopify API (REST, async REST, GraphQL)
- [x] Authentication & installation for shops (both per-user and offline types)
- [x] Plan & billing integration for single, recurring, and usage-types
- [x] Tracking charges to a shop (recurring, single, usage, etc) with trial support
- [x] Auto install app webhooks and scripttags thorugh background jobs
- [x] Provide basic AppBridge views
- [x] Handles and processes incoming webhooks
- [x] Handles and verifies incoming app proxy requests
- [x] Namespacing abilities to run multiple apps on the same database

## Documentation

For full resources on this package, see the [wiki](https://github.com/ohmybrew/laravel-shopify/wiki).

For internal documentation, it is [available here](https://ohmybrew.com/laravel-shopify/) from phpDocumentor.

## Issue or request?

If you have found a bug or would like to request a feature for discussion, please use the `ISSUE_TEMPLATE` in this repo when creating your issue. Any issue submitted without this template will be closed.

## LICENSE

This project is released under the MIT [license](https://github.com/ohmybrew/laravel-shopify/blob/master/LICENSE).

## Misc

### Contributors

Contibutors are updated each release, pulled from Github API. See `CONTRIBUTORS.txt`.

### Special Note

I develop this package in my spare time, with a busy family/work life like many of you! So, I would like to thank everyone who's helped me out from submitting PRs, to assisting on issues, and plain using the package (I hope its useful). Cheers.
