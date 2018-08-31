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
    }

    public function testShopCanBeScopedToNamespaces()
    {
        $shop = new Shop();
        $shop->shopify_domain = 'namespace.myshopify.com';
        $shop->namespace = 'shopify-test';
        $shop->save();

        $shop_2 = new Shop();
        $shop_2->shopify_domain = 'namespace.myshopify.com';
        $shop_2->namespace = 'shopify-test-2';
        $shop_2->save();

        // Test getting all entries for this shop
        $shopEntries = Shop::withoutGlobalScope(NamespaceScope::class)
            ->select('shopify_domain', 'namespace')
            ->where('shopify_domain', 'namespace.myshopify.com')
            ->orderBy('id', 'asc')
            ->get();
        $this->assertEquals('shopify-test', $shopEntries[0]->namespace);
        $this->assertEquals('shopify-test-2', $shopEntries[1]->namespace);

        // Test namespacing config
        config(['shopify-app.namespace' => 'shopify-test']);
        $this->assertEquals('shopify-test', Shop::where('shopify_domain', 'namespace.myshopify.com')->first()->namespace);

        config(['shopify-app.namespace' => 'shopify-test-2']);
        $this->assertEquals('shopify-test-2', Shop::where('shopify_domain', 'namespace.myshopify.com')->first()->namespace);
    }
}
