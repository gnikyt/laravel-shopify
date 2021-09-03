<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Illuminate\Http\Response;
use Osiset\ShopifyApp\Test\TestCase;

class ApiControllerTest extends TestCase
{
    public function testApiWithoutToken(): void
    {
        $shop = factory($this->model)->create();

        $response = $this->getJson('/api', ['HTTP_X-Shop-Domain' => $shop->name]);
        $response->assertStatus(Response::HTTP_BAD_REQUEST);
        $response->assertExactJson(['error' => 'Session token is invalid.']);
    }

    public function testApiWithToken(): void
    {
        $shop = factory($this->model)->create(['name' => 'shop-name.myshopify.com']);

        $response = $this->getJson('/api', [
            'HTTP_X-Shop-Domain' => $shop->name,
            'HTTP_Authorization' => "Bearer {$this->buildToken()}",
        ]);
        $response->assertExactJson([]);
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
