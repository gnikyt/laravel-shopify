<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Test\TestCase;

class ItpControllerTest extends TestCase
{
    public function testAttempt(): void
    {
        $this->call('get', '/itp', [])
            ->assertRedirect()
            ->assertCookie('itp');
    }

    public function testAsk(): void
    {
        $this->call('get', '/itp/ask', [])->assertOk();
    }
}
