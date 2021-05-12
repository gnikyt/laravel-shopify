<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Osiset\ShopifyApp\Test\TestCase;

class ApiControllerTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ShopSession
     */
    protected $shopSession;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testApiWithoutToken(): void
    {
        $shop = factory($this->model)->create();

        $response = $this->getJson('/api', ['HTTP_X-Shop-Domain' => $shop->name]);

        $response->assertStatus(400);
        $response->assertExactJson(['error' => 'Session token is invalid.'], $response->getContent());
    }

    public function testApiWithToken(): void
    {
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        $response = $this->getJson('/api', [
            'HTTP_X-Shop-Domain' => $shop->name,
            'HTTP_Authorization' => "Bearer {$this->buildToken()}",
        ]);

        $response->assertExactJson([], $response->getContent());
        $response->assertOk();
    }

    public function testApiGetSelf(): void
    {
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        $response = $this->getJson('/api/me', [
            'HTTP_X-Shop-Domain' => $shop->name,
            'HTTP_Authorization' => "Bearer {$this->buildToken()}",
        ]);
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'shop-name.myshopify.com']);
    }

    public function testApiGetPlans(): void
    {
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        $response = $this->getJson('/api/me', [
            'HTTP_X-Shop-Domain' => $shop->name,
            'HTTP_Authorization' => "Bearer {$this->buildToken()}",
        ]);

        $response->assertOk();
        $result = json_decode($response->getContent());
        $this->assertNotEmpty($result);
    }
}
