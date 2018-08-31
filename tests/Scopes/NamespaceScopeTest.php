<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Test\TestCase;
use OhMyBrew\ShopifyApp\Scopes\NamespaceScope;

class NamespaceScopeTest extends TestCase
{
    public function testScopeCanApply()
    {
        // Test the default
        $builder = Shop::where('shopify_domain', 'example.myshopify.com');
        $this->assertEquals('select * from "shops" where "shopify_domain" = ? and "shops"."deleted_at" is null and "namespace" is null', $builder->toSql());
        $this->assertEquals(['example.myshopify.com'], $builder->getBindings());

        // Test for a real namespace added
        config(['shopify-app.namespace' => 'shopify']);
        $builder = Shop::where('shopify_domain', 'example.myshopify.com');
        $this->assertEquals('select * from "shops" where "shopify_domain" = ? and "shops"."deleted_at" is null and "namespace" = ?', $builder->toSql());
        $this->assertEquals(['example.myshopify.com', 'shopify'], $builder->getBindings());

        // Reset
        config(['shopify-app.namespace' => null]);
    }
}
