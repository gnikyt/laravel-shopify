<?php

namespace Osiset\ShopifyApp\Test\Requests;

use Illuminate\Support\Facades\Validator;
use Osiset\ShopifyApp\Http\Requests\StoreUsageCharge;
use Osiset\ShopifyApp\Test\TestCase;
use function Osiset\ShopifyApp\createHmac;

class StoreUsageChargeTest extends TestCase
{
    public function testFailsWithNoCode(): void
    {
        $validator = Validator::make(
            [],
            (new StoreUsageCharge())->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertTrue((new StoreUsageCharge())->authorize());
    }

    public function testFailsForInvalidSignature(): void
    {
        $data = [
            'price'       => '1.00',
            'description' => 'Testing',
        ];

        $signature = createHmac(['data' => $data, 'buildQuery' => true], $this->app['config']->get('shopify-app.api_secret'));
        $data['signature'] = $signature;
        $data['price'] = '2.00';

        $storeUsage = new StoreUsageCharge([], $data);
        $validator = Validator::make($data, $storeUsage->rules());
        $storeUsage->withValidator($validator);

        $this->assertTrue($validator->fails());
    }

    public function testPasses(): void
    {
        $data = [
            'price'       => '1.00',
            'description' => 'Testing',
            'redirect'    => '/'
        ];
        $signature = createHmac(['data' => $data, 'buildQuery' => true], $this->app['config']->get('shopify-app.api_secret'));
        $data['signature'] = $signature;

        $storeUsage = new StoreUsageCharge([], $data);
        $validator = Validator::make($data, $storeUsage->rules());
        $storeUsage->withValidator($validator);

        $this->assertFalse($validator->fails());
    }
}