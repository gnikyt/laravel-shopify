<?php

namespace Osiset\ShopifyApp\Test\Actions;

use Osiset\ShopifyApp\Test\TestCase;
use Osiset\ShopifyApp\Services\ThemeHelper;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Actions\VerifyThemeSupport;
use Osiset\ShopifyApp\Objects\Enums\ThemeSupportLevel;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;

class VerifyThemeSupportTest extends TestCase
{
    /**
     * @var \Osiset\ShopifyApp\Actions\InstallShop
     */
    protected $action;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testStoreWithUndefinedMainTheme(): void
    {
        $shop = factory($this->model)->create();
        $action = $this->app->make(VerifyThemeSupport::class);

        $result = call_user_func(
            $action,
            ShopId::fromNative($shop->id)
        );

        $this->assertNotNull($result);
        $this->assertEquals(ThemeSupportLevel::UNSUPPORTED, $result);
    }

    public function testStoreWithFullExtensionSupport(): void
    {
        $shop = factory($this->model)->create();
        $defaultThemeResponse = [0, 1, 2, 3];

        $themeHelperStub = $this->createStub(ThemeHelper::class);
        $themeHelperStub->method('themeIsReady')->willReturn(true);
        $themeHelperStub->method('templateJSONFiles')->willReturn($defaultThemeResponse);
        $themeHelperStub->method('mainSections')->willReturn($defaultThemeResponse);
        $themeHelperStub->method('sectionsWithAppBlock')->willReturn($defaultThemeResponse);

        $action = new VerifyThemeSupport($this->app->make(IShopQuery::class), $themeHelperStub);

        $result = call_user_func(
            $action,
            ShopId::fromNative($shop->id)
        );

        $this->assertNotNull($result);
        $this->assertEquals(ThemeSupportLevel::FULL, $result);
    }

    public function testStoreWithPartialExtensionSupport(): void
    {
        $shop = factory($this->model)->create();
        $defaultThemeResponse = [0, 1, 2, 3];

        $themeHelperStub = $this->createStub(ThemeHelper::class);
        $themeHelperStub->method('themeIsReady')->willReturn(true);
        $themeHelperStub->method('templateJSONFiles')->willReturn($defaultThemeResponse);
        $themeHelperStub->method('mainSections')->willReturn($defaultThemeResponse);
        $themeHelperStub->method('sectionsWithAppBlock')->willReturn([...$defaultThemeResponse, random_int(1, 99)]);

        $action = new VerifyThemeSupport($this->app->make(IShopQuery::class), $themeHelperStub);

        $result = call_user_func(
            $action,
            ShopId::fromNative($shop->id)
        );

        $this->assertNotNull($result);
        $this->assertEquals(ThemeSupportLevel::PARTIAL, $result);
    }

    public function testStoreWithoutExtensionSupport(): void
    {
        $shop = factory($this->model)->create();
        $defaultThemeResponse = [];

        $themeHelperStub = $this->createStub(ThemeHelper::class);
        $themeHelperStub->method('themeIsReady')->willReturn(true);
        $themeHelperStub->method('templateJSONFiles')->willReturn($defaultThemeResponse);
        $themeHelperStub->method('mainSections')->willReturn($defaultThemeResponse);
        $themeHelperStub->method('sectionsWithAppBlock')->willReturn($defaultThemeResponse);

        $action = new VerifyThemeSupport($this->app->make(IShopQuery::class), $themeHelperStub);

        $result = call_user_func(
            $action,
            ShopId::fromNative($shop->id)
        );

        $this->assertNotNull($result);
        $this->assertEquals(ThemeSupportLevel::UNSUPPORTED, $result);
    }
}
