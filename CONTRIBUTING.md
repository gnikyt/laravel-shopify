# Contributing

Thanks for wanting to be a part of this Laravel package! This is a simple and standard guide to assist in how to contribute to this project.

## How can I help?

Well, you can:

+ Tackle any [open issues](https://github.com/osiset/laravel-shopify/issues)
+ Help review [pull requests](https://github.com/osiset/laravel-shopify/pulls)
+ [Update documentation](https://github.com/osiset/laravel-shopify/wiki) in our wiki
+ And more!

You don't have to be a superstar, or someone with experience, you can just dive in and help if you feel you can.

## Adding a new feature?

Its best to:

1. Fork the repository (see adding upstream below)
2. Create a branch such as `cool-new-feature`
3. Create your code
4. Create and run successful tests (see testing below)
5. Submit a pull request

## Found a bug?

Its best to:

1. Ensure the bug was not already reported by [searching all issues](https://github.com/osiset/laravel-shopify/issues?q=).
2. If you're unable to find an open issue addressing the problem, open a new one.
    * Be sure to include a title and clear description, as much relevant information as possible.

## Tackling an open issue?

Its best to:

1. Fork the repository (see adding upstream below)
2. Create a branch such as `issue-(issuenumber)-fix`
3. Create your code
4. Create and run successful tests (see testing below)
5. Submit a pull request
6. Comment on the issue

## Misc

### Adding upstream

If you're forking the repository and wish to keep your copy up-to-date with the master, ensure you run this command:

`git remote add upstream git@github.com:osiset/laravel-shopify.git`

You can then update by simply running:

`git checkout master && git pull upstream master`

### Running tests

#### Locally

We use PHPUnit to run tests. Simply run `composer install`.

Next, run `vendor/bin/phpunit` and the rest will be taken care of. Upon any pull requests and merges.

For quicker tests, be sure to disable coverage with `vendor/bin/phpunit --no-coverage`.

### Checking style

We use PHP-CS-Fixer to check for code style violations.

To lint the code, simply run `composer lint`.

#### Actions

We also utilize Github Actions. Currently it will:

1. Pull the package
2. Test the package against a matrix of PHP and Laravel Versions
3. Confirm coding styling is good
4. Updates coverage results

-----

That's it! Enjoy.
