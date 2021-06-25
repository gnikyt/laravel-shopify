<?php

namespace Osiset\ShopifyApp\Test\Directives;

use Osiset\ShopifyApp\Directives\SessionToken;
use Osiset\ShopifyApp\Test\TestCase;
use Symfony\Component\Routing\Route;

class SessionTokenTest extends TestCase
{
    public function testDirective(): void
    {
        $blade = resolve('blade.compiler');
        $result = $blade->compileString('{{ @sessionToken }}');

        $this->assertStringContainsString((new SessionToken())(), $result);
    }
}
