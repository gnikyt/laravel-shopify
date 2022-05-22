<?php

namespace Osiset\ShopifyApp\Test\Console;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Osiset\ShopifyApp\Console\AddVariablesCommand;
use Osiset\ShopifyApp\Test\TestCase;

class AddVariablesCommandTest extends TestCase
{
    public function testItShouldRun(): void
    {
        $tempEnv = tempnam(sys_get_temp_dir(), 'ENV');

        $this->app->loadEnvironmentFrom($tempEnv);
        $this->app->bootstrapWith([LoadEnvironmentVariables::class]);

        $this
            ->artisan('shopify-app:add:variables')
            ->expectsOutput('All variables will be set')
            ->assertExitCode(0);
    }

    public function testItShouldRunWithAlwaysNo(): void
    {
        $tempEnv = tempnam(sys_get_temp_dir(), 'ENV');
        $command = new AddVariablesCommand();

        foreach ($command->shopifyVariables() as $key => $variable) {
            file_put_contents($tempEnv, PHP_EOL."$key=$variable", FILE_APPEND);
        }

        $this->app->loadEnvironmentFrom($tempEnv);
        $this->app->bootstrapWith([LoadEnvironmentVariables::class]);

        $this
            ->artisan('shopify-app:add:variables --always-no')
            ->expectsOutput('Variable SHOPIFY_APP_NAME already exists. Skipping...')
            ->expectsOutput('Variable SHOPIFY_API_KEY already exists. Skipping...')
            ->expectsOutput('Variable SHOPIFY_API_SECRET already exists. Skipping...')
            ->expectsOutput('Variable SHOPIFY_API_SCOPES already exists. Skipping...')
            ->expectsOutput('Variable AFTER_AUTHENTICATE_JOB already exists. Skipping...')
            ->expectsOutput('All variables will be set')
            ->assertExitCode(0);
    }
}
