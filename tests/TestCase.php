<?php namespace OhMyBrew\ShopifyApp\Test;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use OhMyBrew\ShopifyApp\ShopifyAppProvider;

abstract class TestCase extends OrchestraTestCase
{
    public function setUp()
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            ShopifyAppProvider::class,
        ];
    }
}