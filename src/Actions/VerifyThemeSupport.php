<?php

namespace Osiset\ShopifyApp\Actions;

use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Enums\ThemeSupportLevel;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Osiset\ShopifyApp\Services\ThemeHelper;

/**
 * Activates a plan for a shop.
 */
class VerifyThemeSupport
{
    /**
     * Querier for shops.
     *
     * @var IShopQuery
     */
    protected $shopQuery;

    /**
     * Theme helper.
     *
     * @var ThemeHelper
     */
    protected $themeHelper;

    /**
     * Setup.
     *
     * @param IShopQuery  $shopQuery   The querier for shops.
     * @param ThemeHelper $themeHelper Theme helper.
     *
     * @return void
     */
    public function __construct(
        IShopQuery $shopQuery,
        ThemeHelper $themeHelper
    ) {
        $this->shopQuery = $shopQuery;
        $this->themeHelper = $themeHelper;
    }

    /**
     * Execution.
     *
     * @param ShopId $shopId The shop ID.
     *
     * @return int
     */
    public function __invoke(ShopId $shopId): int
    {
        $this->themeHelper->extractStoreMainTheme($shopId);

        if ($this->themeHelper->themeIsReady()) {
            $templateJSONFiles = $this->themeHelper->templateJSONFiles();
            $templateMainSections = $this->themeHelper->mainSections($templateJSONFiles);
            $sectionsWithAppBlock = $this->themeHelper->sectionsWithAppBlock($templateMainSections);

            $hasTemplates = count($templateJSONFiles) > 0;
            $allTemplatesHasRightType = count($templateJSONFiles) === count($sectionsWithAppBlock);
            $templatesСountWithRightType = count($sectionsWithAppBlock);

            switch (true) {
                case $hasTemplates && $allTemplatesHasRightType:
                    return ThemeSupportLevel::FULL;

                case $templatesСountWithRightType:
                    return ThemeSupportLevel::PARTIAL;

                default:
                    return ThemeSupportLevel::UNSUPPORTED;
            }
        }

        return ThemeSupportLevel::UNSUPPORTED;
    }
}
