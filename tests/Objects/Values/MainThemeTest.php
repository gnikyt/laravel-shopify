<?php

namespace Osiset\ShopifyApp\Test\Objects\Values;

use Osiset\ShopifyApp\Objects\Values\MainTheme;
use Osiset\ShopifyApp\Objects\Values\NullableThemeId;
use Osiset\ShopifyApp\Objects\Values\NullableThemeName;
use Osiset\ShopifyApp\Objects\Values\NullableThemeRole;
use Osiset\ShopifyApp\Test\TestCase;

class MainThemeTest extends TestCase
{
    public const THEME_ID = 1;

    public const THEME_NAME = 'Dawn';

    public const THEME_ROLE = 'main';

    /**
     * @var MainTheme
     */
    protected $mainTheme;

    public function setUp(): void
    {
        parent::setUp();

        $this->mainTheme = MainTheme::fromNative([
            'id' => self::THEME_ID,
            'name' => self::THEME_NAME,
            'role' => self::THEME_ROLE,
        ]);
    }

    public function testGetMainThemeId()
    {
        $themeId = $this->mainTheme->getId();

        $this->assertInstanceOf(NullableThemeId::class, $themeId);
        $this->assertEquals($themeId->toNative(), self::THEME_ID);
    }

    public function testGetMainThemeName()
    {
        $themeName = $this->mainTheme->getName();

        $this->assertInstanceOf(NullableThemeName::class, $themeName);
        $this->assertEquals($themeName->toNative(), self::THEME_NAME);
    }

    public function testGetMainThemeRole()
    {
        $themeRole = $this->mainTheme->getRole();

        $this->assertInstanceOf(NullableThemeRole::class, $themeRole);
        $this->assertEquals($themeRole->toNative(), self::THEME_ROLE);
    }
}
