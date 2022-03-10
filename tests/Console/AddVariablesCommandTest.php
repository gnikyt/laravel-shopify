<?php

namespace Osiset\ShopifyApp\Test\Console;

use Orchestra\Testbench\TestCase;

class AddVariablesCommandTest extends TestCase
{
    public function testItShouldRun(): void
    {
        $this->artisan('shopify-app:add:variables')
            ->expectsQuestion('This will invalidate SHOPIFY_APP_NAME variable. Are you sure you want to override SHOPIFY_APP_NAME?', 'yes')
            ->expectsQuestion('This will invalidate SHOPIFY_API_KEY variable. Are you sure you want to override SHOPIFY_API_KEY?', 'yes')
            ->expectsQuestion('This will invalidate SHOPIFY_API_SECRET variable. Are you sure you want to override SHOPIFY_API_SECRET?', 'yes')
            ->expectsQuestion('This will invalidate SHOPIFY_API_SCOPES variable. Are you sure you want to override SHOPIFY_API_SCOPES?', 'yes')
            ->expectsQuestion('This will invalidate SHOPIFY_REDIRECT_AFTER_SUCCESS_CHARGE variable. Are you sure you want to override SHOPIFY_REDIRECT_AFTER_SUCCESS_CHARGE?', 'yes')
            ->expectsQuestion('This will invalidate AFTER_AUTHENTICATE_JOB variable. Are you sure you want to override AFTER_AUTHENTICATE_JOB?', 'yes')
            ->assertExitCode(0);
    }

    public function testItShouldRunForce(): void
    {
        $this->artisan('shopify-app:add:variables --force')->assertExitCode(0);
    }

    public function testItShouldRunAlwaysNo(): void
    {
        $this->artisan('shopify-app:add:variables --always-no')->assertExitCode(0);
    }
}
