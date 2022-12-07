<?php

namespace Osiset\ShopifyApp\Test\Services;

use Osiset\ShopifyApp\Services\ThemeHelper;
use Osiset\ShopifyApp\Test\Stubs\Api as ApiStub;
use Osiset\ShopifyApp\Test\TestCase;

class ThemeHelperTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Services\ThemeHelper
     */
    protected $helper;

    /**
     * @var \Osiset\ShopifyApp\Contracts\ShopModel
     */
    protected $shop;

    public function setUp(): void
    {
        parent::setUp();

        // Init class and create a shop
        $this->helper = $this->app->make(ThemeHelper::class);
        $this->shop = factory($this->model)->create();
    }

    public function testThemeIsReady(): void
    {
        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_themes', 'get_theme_assets']);

        // Run extraction
        $this->helper->extractStoreMainTheme($this->shop->getId());

        $this->assertTrue($this->helper->themeIsReady());
    }

    public function testThemeIsReadyFailure(): void
    {
        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['empty_with_error']);

        // Run extraction
        $this->helper->extractStoreMainTheme($this->shop->getId());

        $this->assertFalse($this->helper->themeIsReady());
    }

    public function testTemplateJsonFiles(): void
    {
        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_themes', 'get_theme_assets']);

        // Run extraction
        $this->helper->extractStoreMainTheme($this->shop->getId());

        // No config defined, so the match should be empty
        $this->assertEmpty($this->helper->templateJSONFiles());

        // Define config, retest
        $this->app['config']->set(
            'shopify-app.theme_support.templates',
            [
                'shop', // Matches fixture data for `templates/shop.json`
            ]
        );
        $this->assertNotEmpty($this->helper->templateJSONFiles());
    }

    public function testMainSections(): void
    {
        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses(['get_themes', 'get_theme_assets', 'get_theme_asset_template_json']);

        // Define config
        $this->app['config']->set(
            'shopify-app.theme_support.templates',
            [
                'shop', // Matches fixture data for `templates/shop.json`
            ]
        );

        // Run extraction
        $this->helper->extractStoreMainTheme($this->shop->getId());

        // Get the main sections...
        // `templates/shop.json`'s content in `get_theme_asset_template_json` fixture
        // has a main of `product` which converts to `sections/product.liquid` which also
        // exists in `get_theme_assets` fixture
        $jsonFiles = $this->helper->templateJSONFiles();
        $mainSections = $this->helper->mainSections($jsonFiles);

        $this->assertNotEmpty($mainSections);
    }

    public function testSectionsWithAppBlock(): void
    {
        // Response stubbing
        $this->setApiStub();
        ApiStub::stubResponses([
            'get_themes',
            'get_theme_assets',
            'get_theme_asset_template_json',
            'get_theme_asset_product_section',
        ]);

        // Define config
        $this->app['config']->set(
            'shopify-app.theme_support.templates',
            [
                'shop', // Matches fixture data for `templates/shop.json`
            ]
        );

        // Run extraction
        $this->helper->extractStoreMainTheme($this->shop->getId());
        $jsonFiles = $this->helper->templateJSONFiles();
        $mainSections = $this->helper->mainSections($jsonFiles);
        $sectionsWithApp = $this->helper->sectionsWithAppBlock($mainSections);

        $this->assertNotEmpty($sectionsWithApp);
    }
}
