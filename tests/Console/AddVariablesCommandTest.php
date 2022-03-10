<?php

namespace Osiset\ShopifyApp\Test\Console;

use Osiset\ShopifyApp\Test\TestCase;

class AddVariablesCommandTest extends TestCase
{
    public function testItShouldRunForce(): void
    {
        $this->artisan('shopify-app:add:variables --force')->assertExitCode(0);
    }

    public function testItShouldRunAlwaysNo(): void
    {
        $this->artisan('shopify-app:add:variables --always-no')->assertExitCode(0);
    }
}
