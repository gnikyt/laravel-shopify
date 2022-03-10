<?php

namespace Osiset\ShopifyApp\Test\Console;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class AddVariablesCommandTest extends OrchestraTestCase
{
    public function setUp(): void
    {
        parent::setUp();
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
