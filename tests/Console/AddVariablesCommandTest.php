<?php

namespace Osiset\ShopifyApp\Test\Console;

use Osiset\ShopifyApp\Test\TestCase;

class AddVariablesCommandTest extends TestCase
{
    public function testItShouldRun(): void
    {
        $this->artisan('shopify-app:add:variables')
            ->assertExitCode(0);
    }
}
