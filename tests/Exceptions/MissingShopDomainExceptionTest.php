<?php

namespace OhMyBrew\ShopifyApp\Test\Exceptions;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Test\TestCase;

class MissingShopDomainExceptionTest extends TestCase
{
    public function testErrorIsPassedToLoginForNonDebug()
    {
        // Run the request to billing since it is behind AuthShop
        $response = $this->get('/billing');
        $response->assertRedirect('/login');
        $response->assertSessionHas('error', 'Unable to get shop domain.');
    }

    public function testErrorIsThrownForDebug()
    {
        Config::set('app.debug', true);

        $response = $this->get('/billing');
        $response->assertStatus(500);
    }
}
