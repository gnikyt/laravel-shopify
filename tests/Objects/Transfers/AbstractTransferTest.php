<?php

namespace OhMyBrew\ShopifyApp\Objects\Transfers;

use Exception;
use OhMyBrew\ShopifyApp\Test\TestCase;

class AbstractTransferTest extends TestCase
{
    public function testGetterAndSetter(): void
    {
        $transfer = new ApiSession();
        $transfer->domain = 'example.myshopify.com';

        $this->assertEquals($transfer->domain, 'example.myshopify.com');
    }

    public function testBadSetter(): void
    {
        $this->expectException(Exception::class);

        $transfer = new ApiSession();
        $transfer->thisDoesNotExist = 1;
    }

    public function testBadGetter()
    {
        $this->expectException(Exception::class);

        $transfer = new ApiSession();
        return $transfer->thisDoesNotExist;
    }

    public function testIteration()
    {
        $transfer = new ApiSession();
        $transfer->domain = 'example.myshopify.com';
        $transfer->token = '123456';

        foreach ($transfer as $key => $value) {
            $this->assertEquals($value, $transfer->{$key});
        }
    }

    public function testSerialization()
    {
        $transfer = new ApiSession();
        $transfer->domain = 'example.myshopify.com';
        $transfer->token = '123456';

        $this->assertEquals('"{\"domain\":\"example.myshopify.com\",\"token\":\"123456\"}"', json_encode($transfer));
    }
}