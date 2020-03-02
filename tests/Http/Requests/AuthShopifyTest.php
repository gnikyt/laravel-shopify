<?php

namespace OhMyBrew\ShopifyApp\Test\Requests;

use OhMyBrew\ShopifyApp\Test\TestCase;
use Illuminate\Support\Facades\Validator;
use function OhMyBrew\ShopifyApp\createHmac;
use OhMyBrew\ShopifyApp\Http\Requests\AuthShopify;

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