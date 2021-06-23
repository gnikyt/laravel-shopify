<?php

namespace Osiset\ShopifyApp\Test\Traits;

use Illuminate\Support\Facades\Request;
use function Osiset\ShopifyApp\base64url_encode;
use Osiset\ShopifyApp\Objects\Enums\PlanType;
use Osiset\ShopifyApp\Storage\Models\Plan;
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

        $response = $this->get('/api');

        $this->assertSame(401, $response->status());
        $this->assertSame('Missing authentication token', $response->getContent());
    }

    public function testApiWithoutTokenJson(): void
    {
        $shop = factory($this->model)->create();

        $response = $this->get('/api', [
            'accept' => 'application/json',
        ]);

        $this->assertSame(401, $response->status());
        $this->assertSame('{"error":"Missing authentication token"}', $response->getContent());
    }

    public function testApiWithToken(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $response->assertOk();
        $this->assertEmpty(json_decode($response->getContent()));
    }

    public function testApiWithTokenJson(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                'accept' => 'application/json',
            ])
        );

        $response->assertOk();
        $this->assertSame('[]', $response->getContent());
    }

    public function testApiWithExpiredToken(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now - 120,
            'nbf' => $now - 180,
            'iat' => $now - 180,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $this->assertSame(403, $response->status());
        $this->assertSame('Expired token', $response->getContent());
    }

    public function testApiWithExpiredTokenJson(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now - 120,
            'nbf' => $now - 180,
            'iat' => $now - 180,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                'accept' => 'application/json',
            ])
        );

        $this->assertSame(403, $response->status());
        $this->assertSame('{"error":"Expired token"}', $response->getContent());
    }

    public function testApiWithMalformedToken(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $this->assertSame(400, $response->status());
        $this->assertSame('Malformed token', $response->getContent());
    }

    public function testApiWithMalformedTokenJson(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                'accept' => 'application/json',
            ])
        );

        $this->assertSame(400, $response->status());
        $this->assertSame('{"error":"Malformed token"}', $response->getContent());
    }

    public function testApiWithDomainMismatch(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://another-shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $this->assertSame(400, $response->status());
        $this->assertSame('Invalid token', $response->getContent());
    }

    public function testApiWithDomainMismatchJson(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://another-shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                'accept' => 'application/json',
            ])
        );

        $this->assertSame(400, $response->status());
        $this->assertSame('{"error":"Invalid token"}', $response->getContent());
    }

    public function testApiWithInvalidTokenHeader(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('xxxxxx.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $this->assertSame(400, $response->status());
        $this->assertSame('Malformed token', $response->getContent());
    }

    public function testApiWithInvalidTokenHeaderJson(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('xxxxxx.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
                'accept' => 'application/json',
            ])
        );

        $this->assertSame(400, $response->status());
        $this->assertSame('{"error":"Malformed token"}', $response->getContent());
    }

    public function testApiGetSelf(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $response = $this->get('/api/me',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $response->assertOk();
        $this->assertSame('{"name":"shop-name.myshopify.com","shopify_grandfathered":"0","shopify_freemium":"0","plan":null}', $response->getContent());
    }

    public function testApiGetPlans(): void
    {
        $now = $this->now->getTimestamp();

        $body = base64url_encode(json_encode([
            'iss' => 'https://shop-name.myshopify.com/admin',
            'dest' => 'https://shop-name.myshopify.com',
            'aud' => env('SHOPIFY_API_KEY'),
            'sub' => '123',
            'exp' => $now + 60,
            'nbf' => $now,
            'iat' => $now,
            'jti' => '00000000-0000-0000-0000-000000000000',
            'sid' => '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZ',
        ]));

        $payload = sprintf('eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.%s', $body);

        $secret = env('SHOPIFY_API_SECRET');

        $hmac = base64url_encode(hash_hmac('sha256', $payload, $secret, true));

        $token = sprintf('%s.%s', $payload, $hmac);

        $shop = factory($this->model)->create([
            'name' => 'shop-name.myshopify.com',
        ]);

        $plan = factory(Plan::class)->create([
            'type' => PlanType::RECURRING()->toNative(),
        ]);

        $response = $this->get('/api/plans',
            array_merge(Request::server(), [
                'HTTP_AUTHORIZATION' => sprintf('Bearer %s', $token),
            ])
        );

        $result = json_decode($response->getContent());

        $this->assertNotEmpty($result);
        $this->assertNotEmpty($result);
        $this->assertCount(1, $result);
        $this->assertStringContainsString('RECURRING', $response->getContent());
    }
}
