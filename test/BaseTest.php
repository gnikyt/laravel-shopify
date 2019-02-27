<?php

namespace OhMyBrew\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use OhMyBrew\BasicShopifyAPI;
use PHPUnit\Framework\TestCase;

abstract class BaseTest extends TestCase
{
    /**
     * Builds the client for the API.
     *
     * @param BasicShopifyAPI $api
     * @param array           $responses
     *
     * @return MockHandler
     */
    protected function buildClient(BasicShopifyAPI $api, array $responses)
    {
        // Build mock handler
        $mock = new MockHandler($responses);

        // Build stack with auth middleware
        $stack = HandlerStack::create($mock);
        $stack->push(Middleware::mapRequest([$api, 'authRequest']));

        // Build the client
        $client = new Client(['handler' => $stack]);

        // Set the client
        $api->setClient($client);

        return $mock;
    }
}
