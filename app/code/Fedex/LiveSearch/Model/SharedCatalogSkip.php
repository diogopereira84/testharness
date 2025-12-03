<?php
/**
 * @category  Fedex
 * @package   Fedex_LiveSearch
 * @author    Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 * @copyright 2024 Fedex
 */
declare(strict_types=1);

namespace Fedex\LiveSearch\Model;

use Fedex\LiveSearch\Api\Data\SharedCatalogSkipInterface;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Fedex\Commercial\Helper\CommercialHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class SharedCatalogSkip implements SharedCatalogSkipInterface
{
     public const LIVESEARCH_PRODUCTLISTING_WIDGET = 'storefront_features/website_configuration/product_listing_widgets_active';

    /**
     * @param CatalogMvp $catalogMvpHelper
     * @param Registry $registry
     * @param State $state
     * @param CommercialHelper $commercialHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private readonly CatalogMvp $catalogMvpHelper,
        private readonly Registry $registry,
        private readonly State $state,
        private readonly CommercialHelper $commercialHelper,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
    }

    /**
     * Check Is shared catalog page
     *
     * @return bool
     */
    public function checkIsSharedCatalogPage(): bool
    {
        $toggleEnbleForPerformance = $this->catalogMvpHelper->toggleEnbleForPerformance();
        if ($toggleEnbleForPerformance) {
            //This logic is required for NON-category pages
            if (!$this->isCurrentCategoryRootCategory()) {
                $checkPrintCategory = $this->catalogMvpHelper->checkPrintCategory();
                if (!$checkPrintCategory) {
                   return true;
                }
            }
        }

        if (!$toggleEnbleForPerformance) {
        $category =  $this->registry->registry('current_category');
        if($category!==null){
            $checkPrintCategory = $this->catalogMvpHelper->checkPrintCategory();
            if ((!$checkPrintCategory))
                 {
                   return true;
                 }
            }
        }
        return false;
    }

    /**
     * Check commercial store and area is frontend
     *
     * @return bool
     * @throws LocalizedException
     */
    public function checkCommercialStoreWithArea():bool
    {
        $toggleEnbleForPerformance = $this->catalogMvpHelper->toggleEnbleForPerformance();
        if($toggleEnbleForPerformance){
            return $this->commercialHelper->isGlobalCommercialCustomer();
        } else {
            return ($this->state->getAreaCode()=== Area::AREA_FRONTEND) &&
            ($this->commercialHelper->isGlobalCommercialCustomer());

        }

    }
    /**
     * Get store config value for adobe livesearch product listing enable
     *
     * @return string
     */
    public function getLivesearchProductListingEnable()
    {
        return $this->scopeConfig->getValue(
            self::LIVESEARCH_PRODUCTLISTING_WIDGET,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if current category = root category
     *
     * @return bool
     */
    private function isCurrentCategoryRootCategory() {
        $rootCategory = $this->catalogMvpHelper->getRootCategoryFromStore('ondemand');
        $currentCategory = $this->catalogMvpHelper->getCurrentCategory()?->getId();

        return $rootCategory == $currentCategory;
    }
}
