<?php

namespace OhMyBrew\ShopifyApp\Test\Requests;

use Illuminate\Support\Facades\Validator;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Requests\AuthShop;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AuthShopRequestTest extends TestCase
{
    public function testFailsWithNoCode()
    {
        $validator = Validator::make(
            [
                'code' => '1234',
            ],
            (new AuthShop())->rules()
        );

        $this->assertTrue($validator->fails());
    }

    public function testFailsWithInvalidHmac()
    {
        $data = [
            'shop'      => 'test.myshopify.com',
            'code'      => '1234',
            'timestamp' => time(),
            'protocol'  => 'https',
        ];
        $hmac = ShopifyApp::createHmac([
            'data'               => $data,
            'buildQuery'         => true,
            'buildQueryWithJoin' => true,
        ]);

        $data['shop'] = 'oops';

        $authShop = new AuthShop([], $data);
        $validator = Validator::make(
            array_merge($data, ['hmac' => $hmac]),
            $authShop->rules()
        );
        $authShop->withValidator($validator);

        $this->assertTrue($validator->fails());
    }

    public function testPasses()
    {
        $data = [
            'shop'      => 'test.myshopify.com',
            'timestamp' => time(),
            'protocol'  => 'https',
        ];
        $hmac = ShopifyApp::createHmac([
            'data'               => $data,
            'buildQuery'         => true,
            'buildQueryWithJoin' => true,
        ]);
        $data['hmac'] = $hmac;

        $authShop = new AuthShop([], $data);
        $validator = Validator::make(
            array_merge($data, ['hmac' => $hmac]),
            $authShop->rules()
        );
        $authShop->withValidator($validator);

        $this->assertFalse($validator->fails());
    }
}
