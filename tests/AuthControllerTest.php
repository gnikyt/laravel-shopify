<?php namespace OhMyBrew\ShopifyApp\Test;

class AuthControllerTest extends TestCase
{
    public function testBasicTest()
    {
        $response = $this->get('/auth');
        $response->assertStatus(200);
    }
}
