<?php

namespace OhMyBrew\ShopifyApp\Test\Requests;

use Illuminate\Support\Facades\Validator;
use OhMyBrew\ShopifyApp\Facades\ShopifyApp;
use OhMyBrew\ShopifyApp\Http\Requests\StoreUsageCharge;
use OhMyBrew\ShopifyApp\Test\TestCase;
use function OhMyBrew\ShopifyApp\createHmac;

class StoreUsageChargeTest extends TestCase
{
    public function testFailsWithNoCode(): void
    {
        $validator = Validator::make(
            [],
            (new StoreUsageCharge())->rules()
        );

        $this->assertTrue($validator->fails());
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
        ];
        $signature = createHmac(['data' => $data, 'buildQuery' => true], $this->app['config']->get('shopify-app.api_secret'));
        $data['signature'] = $signature;

        $storeUsage = new StoreUsageCharge([], $data);
        $validator = Validator::make($data, $storeUsage->rules());
        $storeUsage->withValidator($validator);

        $this->assertFalse($validator->fails());
    }
}