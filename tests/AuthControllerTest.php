<?php namespace OhMyBrew\ShopifyApp\Test;

class AuthControllerTest extends TestCase
{
    public function testBasicTest()
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }
}
