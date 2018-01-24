<?php namespace OhMyBrew\ShopifyApp\Test\Controllers;

use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Test\Stubs\ApiStub;

class BillingControllerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        // Stub in our API class
        config(['shopify-app.api_class' => new ApiStub]);
    }

    public function testSendsShopToBillingScreen()
    {
        session(['shopify_domain' => 'example.myshopify.com']);

        $response = $this->get('/billing');
        $response->assertViewHas(
            'url',
            'https://example.myshopify.com/admin/charges/1029266947/confirm_recurring_application_charge?signature=BAhpBANeWT0%3D--64de8739eb1e63a8f848382bb757b20343eb414f'
        );
    }
}
