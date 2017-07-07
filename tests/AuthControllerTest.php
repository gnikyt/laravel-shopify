<?php namespace OhMyBrew\ShopifyApp\Test;

class AuthControllerTest extends TestCase
{
    public function testLoginTest()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function testAuthenticateTest()
    {
        $response = $this->post('/login', ['shopify_domain' => 'example.myshopify.com']);
        $response->assertStatus(200);
        $response->assertSessionHas('shopify_domain');
    }
}
