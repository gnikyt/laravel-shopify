<?php

namespace Osiset\ShopifyApp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Osiset\ShopifyApp\Contracts\ShopModel;
use Osiset\ShopifyApp\Objects\Values\MainTheme;
use Osiset\ShopifyApp\Util;

/**
 * Helper for dealing with cookie and cookie issues.
 */
class ThemeHelper
{
    /**
     * Main theme role
     */
    public const MAIN_ROLE = 'main';

    /**
     * Theme field
     */
    public const THEME_FIELD = 'role';

    /**
     * Asset field
     */
    public const ASSET_FIELD = 'key';

    /**
     * Interval for caching the request: minutes, seconds, hours, days, etc.
     *
     * @var string
     */
    protected $cacheInterval;

    /**
     * Cache duration
     *
     * @var int
     */
    protected $cacheDuration;

    /**
     * Shop main theme
     *
     * @var MainTheme
     */
    protected $mainTheme;

    /**
     * Theme assets
     */
    protected $assets;

    /**
     * Constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->cacheInterval = Str::of(Util::getShopifyConfig('theme_support.cache_interval'))
            ->plural()
            ->ucfirst()
            ->start('add')
            ->__toString();
        $this->cacheDuration = Util::getShopifyConfig('theme_support.cache_duration');
    }

    /**
     * Theme is set and ready for testing
     *
     * @return bool
     */
    public function themeIsReady(): bool
    {
        return (bool) $this->mainTheme->getId()->toNative();
    }

    /**
     * Extract store main theme
     *
     * @param ShopModel $shop
     *
     * @return void
     */
    public function extractStoreMainTheme(ShopModel $shop): void
    {
        $themesResponse = $shop->api()->rest('GET', '/admin/themes.json');

        if (!$themesResponse['errors'] && isset($themesResponse['body']['themes'])) {
            $themes = $themesResponse['body']['themes']->toArray();
            $key = array_search($this::MAIN_ROLE, array_column($themes, $this::THEME_FIELD));

            $this->mainTheme = MainTheme::fromNative($themes[$key]);
            $this->extractThemeAssets($shop);
        } else {
            $this->mainTheme = MainTheme::fromNative([]);
        }
    }

    /**
     * Extract main theme assets
     *
     * @param ShopModel $shop
     *
     * @return void
     */
    private function extractThemeAssets(ShopModel $shop): void
    {
        $this->assets = Cache::remember(
            "assets_{$this->mainTheme->getId()->toNative()}",
            now()->{$this->cacheInterval}($this->cacheDuration),
            function () use ($shop) {
                return $shop->api()->rest(
                    'GET',
                    "/admin/themes/{$this->mainTheme->getId()->toNative()}/assets.json"
                )['body']['assets']->toArray();
            }
        );
    }

    /**
     * Check if JSON template files exist for the template specified in APP_BLOCK_TEMPLATES
     *
     * @return array
     */
    public function templateJSONFiles(): array
    {
        return array_filter($this->assets, function ($asset) {
            $match = false;
            $blockTemplates = Util::getShopifyConfig('theme_support.templates');

            foreach ($blockTemplates as $template) {
                if ($asset['key'] == "templates/$template.json") {
                    $match = true;

                    break;
                }
            }

            return $match;
        });
    }

    /**
     * Retrieve the body of JSON templates and find what section is set as `main`
     *
     * @param ShopModel $shop
     * @param array $templateJSONFiles
     *
     * @return array
     */
    public function mainSections(ShopModel $shop, array $templateJSONFiles): array
    {
        return array_filter(array_map(function ($file) use ($shop) {
            $assetResponse = $this->fetchAsset($shop, $file);
            $asset = $assetResponse['body']['asset']->toArray();
            $assetValue = json_decode($asset['value'], true);

            $mainAsset = array_filter($assetValue['sections'], function ($value, $key) {
                return $key == self::MAIN_ROLE || str_starts_with($value['type'], self::MAIN_ROLE);
            }, ARRAY_FILTER_USE_BOTH);

            if ($mainAsset) {
                return array_merge(...array_filter($this->assets, function ($asset) use ($mainAsset) {
                    return $asset['key'] === 'sections/'.end($mainAsset)['type'].'.liquid';
                }));
            }
        }, $templateJSONFiles));
    }

    /**
     * Request the content of each section and check if it has a schema that contains a block of type '@app'
     *
     * @param ShopModel $shop
     * @param array $templateMainSections
     *
     * @return array
     */
    public function sectionsWithAppBlock(ShopModel $shop, array $templateMainSections): array
    {
        return array_filter(array_map(function ($file) use ($shop) {
            $acceptsAppBlock = false;

            $assetResponse = $this->fetchAsset($shop, $file);
            $asset = $assetResponse['body']['asset']->toArray();

            preg_match('/\{\%\s+schema\s+\%\}([\s\S]*?)\{\%\s+endschema\s+\%\}/m', $asset['value'], $matches);
            $schema = json_decode($matches[1], true);

            if ($schema && isset($schema['blocks'])) {
                $acceptsAppBlock = in_array('@app', array_column($schema['blocks'], 'type'));
            }

            return $acceptsAppBlock ? $file : null;
        }, $templateMainSections));
    }

    /**
     * Fetch asset
     *
     * @param ShopModel $shop
     * @param array $file
     *
     * @return array
     */
    private function fetchAsset(ShopModel $shop, array $file): array
    {
        return Cache::remember(
            "asset_{$this->mainTheme->getId()->toNative()}_{$file['key']}",
            now()->{$this->cacheInterval}($this->cacheDuration),
            function () use ($shop, $file) {
                return $shop->api()->rest(
                    'GET',
                    "/admin/themes/{$this->mainTheme->getId()->toNative()}/assets",
                    [
                        'asset' => ['key' => $file['key']],
                    ]
                );
            }
        );
    }
}
