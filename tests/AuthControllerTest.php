<?php namespace OhMyBrew\ShopifyApp\Test;

use Illuminate\Support\Facades\Queue;
use OhMyBrew\ShopifyApp\Jobs\WebhookInstaller;
use OhMyBrew\ShopifyApp\Jobs\ScripttagInstaller;

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

    public function testAuthenticateDoesNotFiresJobsWhenNoConfigForThem()
    {
        Queue::fake();

        $this->post('/login', ['shopify_domain' => 'example.myshopify.com']);

        Queue::assertNotPushed(WebhookInstaller::class);
        Queue::assertNotPushed(ScripttagInstaller::class);
    }

    public function testAuthenticateDoesFiresJobs()
    {
        Queue::fake();
        config(['shopify-app.webhooks' => [
            'orders/create' => 'https://localhost/webhooks/orders-create'
        ]]);
        config(['shopify-app.scripttags' => [
            ['src' => 'https://localhost/scripts/file.js']
        ]]);

        $this->post('/login', ['shopify_domain' => 'example.myshopify.com']);

        Queue::assertPushed(WebhookInstaller::class);
        Queue::assertPushed(ScripttagInstaller::class);
    }
}
