<?php

namespace OhMyBrew\ShopifyApp\Test;

use Carbon\Carbon;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\ShopifyAppProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();

        // Setup database
        $this->setupDatabase($this->app);
        $this->seedDatabase();
    }

    protected function getPackageProviders($app)
    {
        // ConsoleServiceProvider required to make migrations work
        return [
            ShopifyAppProvider::class,
            ConsoleServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        // For the facade
        return [
            'ShopifyApp' => \OhMyBrew\ShopifyApp\Facades\ShopifyApp::class,
        ];
    }

    protected function resolveApplicationHttpKernel($app)
    {
        // For adding custom the shop middleware
        $app->singleton('Illuminate\Contracts\Http\Kernel', 'OhMyBrew\ShopifyApp\Test\Stubs\Kernel');
    }

    protected function getEnvironmentSetUp($app)
    {
        // Use memory SQLite, cleans it self up
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }

    protected function setupDatabase($app)
    {
        // Path to our migrations to load
        $this->loadMigrationsFrom(realpath(__DIR__.'/../src/ShopifyApp/resources/database/migrations'));
    }

    protected function seedDatabase()
    {
        $this->createShops();
        $this->createCharges();
    }

    protected function createShops()
    {
        $shops = [
            // Paid shop, not grandfathered
            [
                'shopify_domain' => 'example.myshopify.com',
                'shopify_token' => '1234',
            ],

            // Non-paid shop, grandfathered
            [
                'shopify_domain' => 'grandfathered.myshopify.com',
                'shopify_token' => '1234',
                'grandfathered' => true,
            ],

            // New shop... non-paid, not grandfathered
            [
                'shopify_domain' => 'new-shop.myshopify.com',
                'shopify_token' => '1234',
            ],

            // New shop... no token, not grandfathered
            [
                'shopify_domain' => 'no-token-shop.myshopify.com',
            ],
        ];

        // Build the shops
        foreach ($shops as $shopData) {
            $shop = new Shop();
            foreach ($shopData as $key => $value) {
                $shop->{$key} = $value;
            }
            $shop->save();
        }

        // Special trashed shop
        $shop = new Shop();
        $shop->shopify_domain = 'trashed-shop.myshopify.com';
        $shop->save();
        $shop->delete();
    }

    public function createCharges()
    {
        $charges = [
            // Test = true, status = accepted, trial = 7, active trial = no
            [
                'charge_id' => 98298298,
                'test' => true,
                'name' => 'Test Plan',
                'status' => 'accepted',
                'type' => 1,
                'price' => 15.00,
                'trial_days' => 7,
                'trial_ends_on' => Carbon::createFromDate(2018, 6, 3, 'UTC')->addWeeks(1)->format('Y-m-d'),
                'shop_id' => Shop::where('shopify_domain', 'example.myshopify.com')->first()->id,
            ],

            // Test = false, status = active, trial = 7, active trial = yes
            [
                'charge_id' => 67298298,
                'test' => false,
                'name' => 'Base Plan',
                'status' => 'active',
                'type' => 1,
                'price' => 25.00,
                'trial_days' => 7,
                'trial_ends_on' => Carbon::today()->addDays(2)->format('Y-m-d'),
                'shop_id' => Shop::where('shopify_domain', 'example.myshopify.com')->first()->id,
            ],

            // Test = false, status = active, trial = 7, active trial = no
            [
                'charge_id' => 78378873,
                'test' => false,
                'name' => 'Base Plan Old',
                'status' => 'active',
                'type' => 1,
                'price' => 25.00,
                'trial_days' => 7,
                'trial_ends_on' => Carbon::today()->subWeeks(4)->format('Y-m-d'),
                'shop_id' => Shop::where('shopify_domain', 'example.myshopify.com')->first()->id,
            ],

            // Test = false, status = active, trial = 0
            [
                'charge_id' => 89389389,
                'test' => false,
                'name' => 'Base Plan Old Non-Trial',
                'status' => 'active',
                'type' => 1,
                'price' => 25.00,
                'trial_days' => 0,
                'shop_id' => Shop::where('shopify_domain', 'example.myshopify.com')->first()->id,
            ],

            // Test = false, status = declined, trial = 7, active trial = true
            [
                'charge_id' => 78378378378,
                'test' => false,
                'name' => 'Base Plan Declined',
                'status' => 'declined',
                'type' => 1,
                'price' => 25.00,
                'shop_id' => Shop::where('shopify_domain', 'no-token-shop.myshopify.com')->first()->id,
            ],

            // Test = false, status = cancelled
            [
                'charge_id' => 783873873,
                'test' => false,
                'name' => 'Base Plan Cancelled',
                'status' => 'active',
                'type' => 1,
                'price' => 25.00,
                'shop_id' => Shop::where('shopify_domain', 'example.myshopify.com')->first()->id,
                'cancelled_on' => Carbon::today()->format('Y-m-d'),
            ],

            // Test = false, status = cancelled, trial = 7
            [
                'charge_id' => 928736721,
                'test' => false,
                'name' => 'Base Plan Cancelled',
                'status' => 'cancelled',
                'type' => 1,
                'price' => 25.00,
                'trial_days' => 7,
                'trial_ends_on' => Carbon::today()->addWeeks(1)->format('Y-m-d'),
                'cancelled_on' => Carbon::today()->addDays(2)->format('Y-m-d'),
                'shop_id' => Shop::withTrashed()->where('shopify_domain', 'trashed-shop.myshopify.com')->first()->id,
            ],
        ];

        // Build the charges
        foreach ($charges as $chargeData) {
            $charge = new Charge();
            foreach ($chargeData as $key => $value) {
                $charge->{$key} = $value;
            }
            $charge->save();
        }
    }
}
