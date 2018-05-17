<?php

namespace OhMyBrew\ShopifyAPI;

class GraphApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @test
     *
     * Checking base URL for API calls on public
     */
    public function itShouldReturnBaseUrl()
    {
        $api = new GraphAPI();
        $api->setShop('example.myshopify.com');

        $this->assertEquals('https://example.myshopify.com/admin/api/graphql.json', $api->getBaseUrl());
    }
}
