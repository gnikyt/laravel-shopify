<?php

namespace OhMyBrew\ShopifyApp\Test\Exceptions;

use OhMyBrew\ShopifyApp\Test\TestCase;

class MissingShopDomainExceptionTest extends TestCase
{
    public function testErrorIsPassedToLoginForProduction()
    {
        $this->swapEnvironment('production', function () {
            // Run the request to billing since it is behind AuthShop
            $response = $this->get('/billing');
            $response->assertRedirect('/login');
            $response->assertSessionHas('error', 'Unable to get shop domain.');
        });
    }

    public function testErrorIsThrownForNonProduction()
    {
        $response = $this->get('/billing');
        $response->assertStatus(500);
    }
}
