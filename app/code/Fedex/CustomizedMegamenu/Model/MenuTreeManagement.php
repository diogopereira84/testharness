<?php

declare(strict_types=1);

namespace Fedex\CustomizedMegamenu\Model;

use Fedex\CatalogDocumentUserSettings\Helper\Data as HelperData;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\CustomizedMegamenu\Api\MenuTreeManagementInterface;
use Fedex\Delivery\Helper\Data;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Customer\Model\Session;
use Magento\Customer\Model\SessionFactory;

class MenuTreeManagement implements MenuTreeManagementInterface
{
    public const TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE = 'nfr_catelog_performance_improvement_phase_one';
    public const EPRO_PRINT_CUSTOM_PRODUCT = 'ondemand_setting/category_setting/epro_print_custom_product';
    public const EPRO_PRINT_SKUONLY_PRODUCT = 'ondemand_setting/category_setting/epro_print_skuonly_product';
    public const EXPLORES_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';

    /**
     * @param CategoryFactory $categoryFactory
     * @param ToggleConfig $toggleConfig
     * @param SessionFactory $session
     * @param Data $deliveryHelper
     * @param HelperData $helperData
     * @param CatalogMvp $catalogMvpHelper
     * @param Session $customerSession
     */
    public function __construct(
        protected CategoryFactory                $categoryFactory,
        private ToggleConfig                     $toggleConfig,
        private SessionFactory                   $session,
        public Data                              $deliveryHelper,
        public HelperData                        $helperData,
        private CatalogMvp                       $catalogMvpHelper,
        private Session                          $customerSession,
        private readonly OndemandConfigInterface $ondemandConfig,
    ) {}

    /**
     * @param $outermostClass
     * @param $childrenWrapClass
     * @param $limit
     * @param $sharedCatalogCategoryId
     * @param $printProductCategoryId
     * @param $isAdminUser
     * @param $denyCategoryIds
     * @param $categoriesName
     * @return string
     */
    public function renderMegaMenuHtmlOptimized(
        $outermostClass,
        $childrenWrapClass,
        $limit,
        $sharedCatalogCategoryId,
        $printProductCategoryId,
        $isAdminUser,
        $denyCategoryIds,
        $categoriesName
    ): string
    {
        $html = '';
        $pathFilters = [];
        $categoryNameMap = [];
        $isAdminUser = $this->catalogMvpHelper->isSharedCatalogPermissionEnabled();
        $customProductCategoryId = $this->toggleConfig->getToggleConfig(self::EPRO_PRINT_CUSTOM_PRODUCT);
        $skuOnlyProductCategoryId = $this->toggleConfig->getToggleConfig(self::EPRO_PRINT_SKUONLY_PRODUCT);
        $isNonStandardCatalog = $this->toggleConfig->getToggleConfigValue(self::EXPLORES_NON_STANDARD_CATALOG);
        $customerCompanyInfo = $this->getCustomerCompanyInfo();
        $companyType = $customerCompanyInfo['company_type'] ?? '';
        //$printProductCategoryId will be array only when toggle D-239305 is enabled
        if (is_array($printProductCategoryId)) {
            $b2bGeneralCategories = $printProductCategoryId;
            foreach ($b2bGeneralCategories as $b2bGeneralCategory) {
                $categoryNameMap[$b2bGeneralCategory] = $categoriesName[$b2bGeneralCategory];
            }

            array_unshift($b2bGeneralCategories, $sharedCatalogCategoryId);
            $rootCategoryIds = array_filter($b2bGeneralCategories);
        } else {
            if ($printProductCategoryId) {
                $categoryNameMap[$printProductCategoryId] = "Print Products";
            }

            $rootCategoryIds = array_filter([$sharedCatalogCategoryId, $printProductCategoryId]);
        }
        if ($sharedCatalogCategoryId) {
            $categoryNameMap[$sharedCatalogCategoryId] = "Shared Catalog";
        }
        $categoryCollection = $this->categoryFactory->create()->getCollection()
            ->addAttributeToSelect(['name', 'url', 'parent_id', 'is_active', 'level'])
            ->addAttributeToFilter('is_active', 1);

        $extraB2BCategoriesIds = [
            $this->ondemandConfig->getB2bOfficeSuppliesCategory(),
            $this->ondemandConfig->getB2bSPMSuppliesCategory()
        ];
        foreach ($rootCategoryIds as $rootCategoryId) {
            if ($rootCategoryId) {
                $pathFilters[] = ['like' => '%/' . $rootCategoryId];

                if (!$this->ondemandConfig->isTigerD239305ToggleEnabled() ||
                    ($this->ondemandConfig->isTigerD239305ToggleEnabled() &&
                        !in_array($rootCategoryId, $extraB2BCategoriesIds))
                ) {
                    $pathFilters[] = ['like' => '%/' . $rootCategoryId . '/%'];
                }
            }
        }
        if (!empty($pathFilters)) {
            $categoryCollection->addAttributeToFilter('path', $pathFilters);
        }
        if (!$isAdminUser && in_array($companyType, ['selfreg', 'epro'])) {
            $categoryCollection->addAttributeToFilter('is_publish', 1);
        }
        if (!empty($denyCategoryIds)) {
            $categoryCollection->addAttributeToFilter('entity_id', ['nin' => $denyCategoryIds]);
        }
        $categoryCollection->setOrder('position', 'ASC');

        $categoriesByParent = [];
        $categoryItems = [];
        foreach ($categoryCollection as $category) {
            $parentId = $category->getParentId();
            $categoriesByParent[$parentId][] = $category;
            $categoryItems[$category->getId()] = $category;
        }
        $this->sortCategoriesByName($categoriesByParent);
        $counter = 0;

        foreach ($rootCategoryIds as $rootCategoryId) {
            if (!isset($categoryItems[$rootCategoryId])) {
                continue;
            }
            $isPrintProduct= false;
            $category = $categoryItems[$rootCategoryId];
            $nav = 'nav-' . (++$counter);
            $name = $categoryNameMap[$category->getId()] ?? $category->getName();
            $level = max(0, $category->getLevel() - 2);
            $childrenExist = isset($categoriesByParent[$category->getId()]);
            $parentClass = $childrenExist ? 'parent' : '';

            $html .= '<li class="level' . $level . ' ' . $nav . ' category-item ' . $parentClass . '">';
            $html .= '<a href="' . $category->getUrl() . '" class="level-top"><span>' . $name . '</span></a>';
            if ($childrenExist) {
                if ($category->getId() == $this->ondemandConfig->getB2bPrintProductsCategory()) {
                    $isPrintProduct = true;
                }
                $html .= $this->renderCategoryTreeHtml(
                    $category->getId(),
                    $nav,
                    $level,
                    $categoriesByParent,
                    $isAdminUser,
                    $companyType,
                    $isPrintProduct,
                    $customProductCategoryId,
                    $skuOnlyProductCategoryId,
                    $isNonStandardCatalog
                );
            }

            $html .= '</li>';
        }

        return $html;
    }

    /**
     * @param $parentId
     * @param $node
     * @param $parentLevel
     * @param array $categoriesByParent
     * @param $isAdminUser
     * @param $companyType
     * @param $isPrintProduct
     * @param $customProductCategoryId
     * @param $skuOnlyProductCategoryId
     * @param $isNonStandardCatalog
     * @return string
     */
    public function renderCategoryTreeHtml(
        $parentId,
        $node,
        $parentLevel,
        array $categoriesByParent,
        $isAdminUser,
        $companyType,
        $isPrintProduct,
        $customProductCategoryId,
        $skuOnlyProductCategoryId,
        $isNonStandardCatalog
    ): string
    {
        if (empty($categoriesByParent[$parentId])) {
            return '';
        }
        $html = '<ul class="level' . $parentLevel . ' submenu">';
        $categories = $categoriesByParent[$parentId];
        $counter = 0;
        $count = count($categories);

        foreach ($categories as $key => $category) {
            $categoryId = $category->getId();

            if ($isNonStandardCatalog &&
                in_array($categoryId, [$customProductCategoryId, $skuOnlyProductCategoryId])
            ) {
                continue;
            }
            $productCount = $category->getProductCollection()->count();
            if (!$productCount && $isPrintProduct) {
                continue;
            }
            $nav = $node . '-' . (++$counter);
            $level = max(0, $category->getLevel() - 2);
            $first = ($key == 0) ? 'first' : '';
            $last = ($key == $count - 1) ? 'last' : '';
            $hasChildren = isset($categoriesByParent[$categoryId]);
            $parentClass = $hasChildren ? 'parent' : '';
            $html .= '<li class="level' . $level . ' ' . $nav . ' category-item ' . $first . ' ' . $parentClass . ' ' . $last . '">';
            $html .= '<a href="' . $category->getUrl() . '"><span>' . $category->getName() . '</span></a>';
            if ($hasChildren) {
                $html .= $this->renderCategoryTreeHtml(
                    $categoryId,
                    $nav,
                    $level,
                    $categoriesByParent,
                    $isAdminUser,
                    $companyType,
                    $isPrintProduct,
                    $customProductCategoryId,
                    $skuOnlyProductCategoryId,
                    $isNonStandardCatalog
                );
            }
            $html .= '</li>';
        }
        $html .= '</ul>';
        return $html;
    }

    /**
     * @return array
     */
    public function getCustomerCompanyInfo(): array
    {
        if ($this->getToggleStatusForPerformanceImprovmentPhasetwo()) {
            $info = $this->getOrCreateCustomerSession()->getOndemandCompanyInfo();
        } else {
            $info = $this->session->create()->getOndemandCompanyInfo();
        }
        return is_array($info) ? $info : [];
        }

    /**
     * @param array $categoriesByParent
     * @return void
     */
    private function sortCategoriesByName(array &$categoriesByParent): void
    {
        foreach ($categoriesByParent as &$categoryList) {
            usort($categoryList, function ($a, $b) {
                return strcasecmp($a->getName(), $b->getName());
            });
        }
    }
    /**
     * Toggle for Catalog Performance Improvement Phase Two
     * @return bool
     */
    public function getToggleStatusForPerformanceImprovmentPhasetwo()
    {
        return (bool) $this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_NFR_PERFORMANCE_IMPROVEMENT_PHASE_ONE);
    }

    /**
     * Get Customer Session Catalog Improvement Phase Two
     * @return Session
     */
    public function getOrCreateCustomerSession()
    {
        if(!$this->customerSession->isLoggedIn()){
            $this->customerSession = $this->session->create();
        }
        return $this->customerSession;
    }

}
