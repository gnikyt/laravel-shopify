<?php

namespace OhMyBrew\ShopifyApp\Test\Models;

use Illuminate\Support\Facades\Config;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Scopes\NamespaceScope;
use OhMyBrew\ShopifyApp\Test\TestCase;

class NamespaceScopeTest extends TestCase
{
    public function testScopeCanApply()
    {
        // Test the default
        $shop = factory(Shop::class)->create();
        $builder = Shop::where('shopify_domain', $shop->shopify_domain);

        $this->assertEquals('select * from "shops" where "shopify_domain" = ? and "shops"."deleted_at" is null and "namespace" is null', $builder->toSql());
        $this->assertEquals([$shop->shopify_domain], $builder->getBindings());

        // Test for a real namespace added
        Config::set('shopify-app.namespace', 'shopify');
        $shop = factory(Shop::class)->create();
        $builder = Shop::where('shopify_domain', $shop->shopify_domain);

        $this->assertEquals('select * from "shops" where "shopify_domain" = ? and "shops"."deleted_at" is null and "namespace" = ?', $builder->toSql());
        $this->assertEquals([$shop->shopify_domain, 'shopify'], $builder->getBindings());
    }

    public function testShopCanBeScopedToNamespaces()
    {
        $shop = factory(Shop::class)->create([
            'namespace' => 'shopify-test',
        ]);
        $shop_2 = factory(Shop::class)->create([
            'shopify_domain' => $shop->shopify_domain,
            'namespace'      => 'shopify-test-2',
        ]);

        // Test getting all entries for this shop
        $shopEntries = Shop::withoutGlobalScope(NamespaceScope::class)
            ->select('shopify_domain', 'namespace')
            ->where('shopify_domain', $shop->shopify_domain)
            ->orderBy('id', 'asc')
            ->get();
        $this->assertEquals('shopify-test', $shopEntries[0]->namespace);
        $this->assertEquals('shopify-test-2', $shopEntries[1]->namespace);

        // Test namespacing config
        Config::set('shopify-app.namespace', 'shopify-test');
        $this->assertEquals('shopify-test', Shop::where('shopify_domain', $shop->shopify_domain)->first()->namespace);

        Config::set('shopify-app.namespace', 'shopify-test-2');
        $this->assertEquals('shopify-test-2', Shop::where('shopify_domain', $shop->shopify_domain)->first()->namespace);
    }
}
