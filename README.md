# Laravel Shopify App

[![Build Status](https://secure.travis-ci.org/ohmybrew/laravel-shopify.png?branch=master)](http://travis-ci.org/ohmybrew/laravel-shopify)
[![Coverage Status](https://coveralls.io/repos/github/ohmybrew/laravel-shopify/badge.svg?branch=master)](https://coveralls.io/github/ohmybrew/laravel-shopify?branch=master)
[![StyleCI](https://styleci.io/repos/96462257/shield?branch=master)](https://styleci.io/repos/96462257)
[![License](https://poser.pugx.org/ohmybrew/laravel-shopify/license)](https://packagist.org/packages/ohmybrew/laravel-shopify)

**[2020-01-13] As a notice to all: I am currently refactoring the package in a branch to be more manageable. This package has grown exponentially beyond what I had expected and has become a little cumbersome to manage. The new structure will is simply a shuffle of existing code and minor refactors so future contributions, maintenance, and issues will be much easier to handle. I will try to handle the open issues currently in the queue and issue patch releases as I am doing this -- however I can not guarantee this as I am only one person. Please bare with me... thank you!**

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

