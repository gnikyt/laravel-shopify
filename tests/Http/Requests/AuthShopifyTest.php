<?php

namespace Osiset\ShopifyApp\Test\Requests;

use Osiset\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Validator;
use function Osiset\ShopifyApp\createHmac;
use Osiset\ShopifyApp\Http\Requests\AuthShopify;

class AuthShopifyTest extends TestCase
{
    public function testFailsWithNoCode(): void
    {
        $validator = Validator::make(
            [
                'code' => '1234',
            ],
            (new AuthShopify())->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue((new AuthShopify())->authorize());
    }

    public function testFailsWithInvalidHmac(): void
    {
        $data = [
            'shop'      => 'test.myshopify.com',
            'code'      => '1234',
            'timestamp' => time(),
            'protocol'  => 'https',
        ];
        $hmac = createHmac([
            'data'               => $data,
            'buildQuery'         => true,
            'buildQueryWithJoin' => true,
        ], $this->app['config']->get('shopify-app.api_secret'));

        $data['shop'] = 'oops';

        $authShop = new AuthShopify([], $data);
        $validator = Validator::make(
            array_merge($data, ['hmac' => $hmac]),
            $authShop->rules()
        );
        $authShop->withValidator($validator);

        $this->assertTrue($validator->fails());
    }

    public function testPasses(): void
    {
        $data = [
            'shop'      => 'test.myshopify.com',
            'timestamp' => time(),
            'protocol'  => 'https',
        ];
        $hmac = createHmac([
            'data'               => $data,
            'buildQuery'         => true,
            'buildQueryWithJoin' => true,
        ], $this->app['config']->get('shopify-app.api_secret'));
        $data['hmac'] = $hmac;

        $authShop = new AuthShopify([], $data);
        $validator = Validator::make(
            array_merge($data, ['hmac' => $hmac]),
            $authShop->rules()
        );
        $authShop->withValidator($validator);

        $this->assertFalse($validator->fails());
    }
}