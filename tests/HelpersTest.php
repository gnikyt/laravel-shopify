<?php

namespace Osiset\ShopifyApp;

use Osiset\ShopifyApp\Test\TestCase;

class HelpersTest extends TestCase
{
    public function testHmacCreator(): void
    {
        // Set the secret to use for HMAC creations
        $secret = 'hello';

        // Raw data
        $data = 'one-two-three';
        $this->assertEquals(
            hash_hmac('sha256', $data, $secret, true),
            createHmac(['data' => $data, 'raw' => true], $secret)
        );

        // Raw data encoded
        $data = 'one-two-three';
        $this->assertEquals(
            base64_encode(hash_hmac('sha256', $data, $secret, true)),
            createHmac(['data' => $data, 'raw' => true, 'encode' => true], $secret)
        );

        // Query build (sorts array and builds query string)
        $data = ['one' => 1, 'two' => 2, 'three' => 3];
        $this->assertEquals(
            hash_hmac('sha256', 'one=1three=3two=2', $secret, false),
            createHmac(['data' => $data, 'buildQuery' => true], $secret)
        );
    }
}