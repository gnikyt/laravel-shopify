<?php

namespace OhMyBrew\ShopifyApp\Test;

use OhMyBrew\ShopifyApp\Models\Shop;
use OhMyBrew\ShopifyApp\Models\Charge;
use OhMyBrew\ShopifyApp\ShopifyAppProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Carbon\Carbon;

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
        // Paid shop, not grandfathered
        $shop = new Shop();
        $shop->shopify_domain = 'example.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->save();

        // Non-paid shop, grandfathered
        $shop = new Shop();
        $shop->shopify_domain = 'grandfathered.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->grandfathered = true;
        $shop->save();

        // New shop... non-paid, not grandfathered
        $shop = new Shop();
        $shop->shopify_domain = 'new-shop.myshopify.com';
        $shop->shopify_token = '1234';
        $shop->save();

        // New shop... no token, not grandfathered
        $shop = new Shop();
        $shop->shopify_domain = 'no-token-shop.myshopify.com';
        $shop->save();

        // Trashed shop
        $shop = new Shop();
        $shop->shopify_domain = 'trashed-shop.myshopify.com';
        $shop->save();
        $shop->delete();
    }

    public function createCharges()
    {;
        $charge = new Charge();
        $charge->charge_id = 98298298;
        $charge->test = true;
        $charge->name = 'Test Plan';
        $charge->status = 'accepted';
        $charge->type = 1;
        $charge->price = 15.00;
        $charge->trial_days = 7;
        $charge->trial_ends_on = Carbon::createFromDate(2018, 6, 3, 'UTC')->addWeeks(1);
        $charge->shop_id = Shop::where('shopify_domain', 'example.myshopify.com')->first()->id;
        $charge->save();

        $charge = new Charge();
        $charge->charge_id = 67298298;
        $charge->test = false;
        $charge->name = 'Base Plan';
        $charge->status = 'accepted';
        $charge->type = 1;
        $charge->price = 25.00;
        $charge->trial_days = 7;
        $charge->trial_ends_on = Carbon::now()->addDays(2);
        $charge->shop_id = Shop::where('shopify_domain', 'example.myshopify.com')->first()->id;
        $charge->save();

        $charge = new Charge();
        $charge->charge_id = 78378873;
        $charge->test = false;
        $charge->name = 'Base Plan Old';
        $charge->status = 'accepted';
        $charge->type = 1;
        $charge->price = 25.00;
        $charge->trial_days = 7;
        $charge->trial_ends_on = Carbon::now()->subWeeks(4);
        $charge->shop_id = Shop::where('shopify_domain', 'example.myshopify.com')->first()->id;
        $charge->save();

        $charge = new Charge();
        $charge->charge_id = 89389389;
        $charge->test = false;
        $charge->name = 'Base Plan Old Non-Trial';
        $charge->status = 'accepted';
        $charge->type = 1;
        $charge->price = 25.00;
        $charge->trial_days = 0;
        $charge->shop_id = Shop::where('shopify_domain', 'example.myshopify.com')->first()->id;
        $charge->save();
    }
}
