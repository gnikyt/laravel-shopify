<?php

namespace Osiset\ShopifyApp\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Contracts\ShopModel as IShopModel;
use Osiset\ShopifyApp\Objects\Values\MainTheme;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Util;

/**
 * Helper for dealing with theme and theme assets.
 */
class ThemeHelper
{
    /**
     * Main theme role.
     */
    public const MAIN_ROLE = 'main';

    /**
     * Theme field.
     */
    public const THEME_FIELD = 'role';

    /**
     * Asset field.
     */
    public const ASSET_FIELD = 'key';

    /**
     * Current shop instance.
     *
     * @var IShopModel
     */
    protected $shop;

    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Interval for caching the request: minutes, seconds, hours, days, etc.
     *
     * @var string
     */
    protected $cacheInterval;

    /**
     * Cache duration.
     *
     * @var int
     */
    protected $cacheDuration;

    /**
     * Shop main theme.
     *
     * @var MainTheme
     */
    protected $mainTheme;

    /**
     * Theme assets.
     */
    protected $assets;

    /**
     * Setup.
     *
     * @param IShopQuery $shopQuery The querier for shops.
     *
     * @return void
     */
    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;

        $this->cacheInterval = (string) Str::of(Util::getShopifyConfig('theme_support.cache_interval'))
            ->plural()
            ->ucfirst()
            ->start('add');

        $this->cacheDuration = Util::getShopifyConfig('theme_support.cache_duration');
    }

    /**
     * Determine if theme is set and ready for testing.
     *
     * @return bool
     */
    public function themeIsReady(): bool
    {
        return (bool) $this->mainTheme->getId()->toNative();
    }

    /**
     * Extract store's main theme.
     *
     * @param ShopId $shopId The shop ID.
     *
     * @return void
     */
    public function extractStoreMainTheme(ShopId $shopId): void
    {
        $this->shop = $this->shopQuery->getById($shopId);

        $themesResponse = $this->shop->api()->rest('GET', '/admin/themes.json');
        if (!$themesResponse['errors'] && isset($themesResponse['body']['themes'])) {
            $themes = $themesResponse['body']['themes']->toArray();
            $key = array_search(self::MAIN_ROLE, array_column($themes, self::THEME_FIELD));

            $this->mainTheme = MainTheme::fromNative($themes[$key]);
            $this->extractThemeAssets();
        } else {
            $this->mainTheme = MainTheme::fromNative([]);
        }
    }

    /**
     * Extract main theme's assets.
     *
     * @return void
     */
    private function extractThemeAssets(): void
    {
        $this->assets = Cache::remember(
            "assets_{$this->mainTheme->getId()->toNative()}",
            now()->{$this->cacheInterval}($this->cacheDuration),
            function () {
                return $this->shop->api()->rest(
                    'GET',
                    "/admin/themes/{$this->mainTheme->getId()->toNative()}/assets.json"
                )['body']['assets']->toArray();
            }
        );
    }

    /**
     * Check if JSON template files exist for
     * the template specified in `APP_BLOCK_TEMPLATES`.
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
     * Retrieve the body of JSON templates and find what section is set as `main`.
     *
     * @param array $templateJSONFiles The template files to filter through.
     *
     * @return array
     */
    public function mainSections(array $templateJSONFiles): array
    {
        return array_filter(array_map(function ($file) {
            $assetResponse = $this->fetchAsset($file);
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
     * Request the contents of each section then check
     * if it has a schema that contains a block of type '@app'.
     *
     * @param array $templateMainSections The template's main sections to filter through.
     *
     * @return array
     */
    public function sectionsWithAppBlock(array $templateMainSections): array
    {
        return array_filter(array_map(function ($file) {
            $acceptsAppBlock = false;

            $assetResponse = $this->fetchAsset($file);
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
     * Fetch a theme's asset.
     *
     * @param array $file The theme file information to fetch or retrieve from cache.
     *
     * @return array
     */
    private function fetchAsset(array $file): array
    {
        return Cache::remember(
            "asset_{$this->mainTheme->getId()->toNative()}_{$file['key']}",
            now()->{$this->cacheInterval}($this->cacheDuration),
            function () use ($file) {
                return $this->shop->api()->rest(
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
