<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\Categories as CoreCategories;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Locator\LocatorInterface;
use \Magento\Backend\Model\Session as AdminSession;
use Magento\Catalog\Model\Product;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class Categories
{
    private const CATEGORY_UNPUBLISHED = '(Unpublished)';

    private const EXPLORER_ENABLE_DISABLE_CATALOG_CREATION_CTC_ADMIN_UPDATE =
        'explorers_enable_disable_catalog_creation_ctc_admin_update';

         
    public const TECH_TITANS_E484727_COMMERCIAL_CATALOG_TYPE = 'tech_titans_e_484727';

    /**
     *
     * @param CatalogMvp $helper,
     * @param CategoryCollectionFactory $categoryCollectionFactory,
     * @param LocatorInterface $locator,
     * @param AdminSession $adminSession,
     * @param Product $product
     *
     */

    public function __construct(
        protected CatalogMvp $helper,
        protected CategoryCollectionFactory $categoryCollectionFactory,
        protected LocatorInterface $locator,
        protected AdminSession $adminSession,
        protected Product $product,
        private ToggleConfig $toggleConfig
    )
    {
    }

    public function afterModifyMeta(
        CoreCategories $subject,
        $result
        ): array {

        $prod = $this->locator->getProduct();
        $prodId = $prod->getId();
        // Get attribute set ID and product type
        $prodAttributeSetId = $prod->getAttributeSetId();
        $productType = $prod->getTypeId();

        // Save attribute set ID in session
        $this->adminSession->setAttributeSetId($prodAttributeSetId);

        // case to check toggle off
        if (!$this->helper->isMvpCtcAdminEnable()) {
            return $result;
        }

        // Get attribute set name
        $attributeSetName = $this->helper->getAttributeSetName($prodAttributeSetId);
        
        if ($attributeSetName !== 'PrintOnDemand' || $productType !== 'commercial') {
            return $result;
        }

        // Check if product is editable
        if ($prodId) {
            $this->adminSession->setProductId($prodId);
            $podEditable = $this->helper->isProductPodEditAbleById($prodId);
            if (!$podEditable){
                return $result;
            }
        }

        $storeId = (int) $this->locator->getStore()->getId();
        $rootCategoryId = $this->helper->getRootCategoryFromStore('ondemand');
        $B2bPrintCategory = $this->helper->getScopeConfigValue("ondemand_setting/category_setting/epro_print");

        $categoryIds = $this->helper->getSubCategoryByParentID($rootCategoryId, $B2bPrintCategory);

        // Remove B2bPrintCategory from root category
        if (($key = array_search($B2bPrintCategory, $categoryIds)) !== false) {
            unset($categoryIds[$key]);
        }
        $filter = '';
        $categoriesTree = $this->retrieveCategoriesTree(
            $storeId,
            $this->retrieveShownCategoriesIdsnew($storeId, $categoryIds,(string) $filter)
        );

        // Sort categories if conditions are met
        if ($this->toggleConfig->getToggleConfigValue(self::TECH_TITANS_E484727_COMMERCIAL_CATALOG_TYPE)) {
            $categoriesTree = $this->sortCategories($categoriesTree);
        }

        $result['product-details']['children']['container_category_ids']['children']['category_ids']['arguments']['data']['config']['options'] = $categoriesTree;

        return $result;
    }


    /**
     * Retrieve tree of categories with attributes.
     * @param int $storeId
     * @param array $shownCategoriesIds
     * @return array|null
     * @throws LocalizedException
     *
     */
    private function retrieveCategoriesTree(int $storeId, array $shownCategoriesIds) : ?array
    {
        $rooatCategoryDeatail = $this->helper->getRootCategoryDetailFromStore('ondemand');
        $b2bCategoryName = $rooatCategoryDeatail['name'] ?? "B2B Root Category";
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToFilter('entity_id', ['in' => array_keys($shownCategoriesIds)])
            ->addAttributeToSelect(['name', 'is_active', 'parent_id','is_publish'])
            ->setStoreId($storeId);

        $categoryById = [
            CategoryModel::TREE_ROOT_ID => [
                'value' => CategoryModel::TREE_ROOT_ID,
                'optgroup' => null,
            ],
        ];

        foreach ($collection as $category) {
            foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                if (!isset($categoryById[$categoryId])) {
                    $categoryById[$categoryId] = ['value' => $categoryId];
                }
            }

            $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
            $explorersCatalogAdminCtcUpdate = (bool) $this->toggleConfig->getToggleConfigValue(
                static::EXPLORER_ENABLE_DISABLE_CATALOG_CREATION_CTC_ADMIN_UPDATE
            );
            $isPublish = $category->getIsPublish();
            if ($explorersCatalogAdminCtcUpdate &&
                isset($isPublish) &&
                $isPublish == 0 &&
                ($category->getName() != $b2bCategoryName)
            ) {
                $categoryById[$category->getId()]['label'] = $category->getName(). ' '. self::CATEGORY_UNPUBLISHED;
            } else {
                $categoryById[$category->getId()]['label'] = $category->getName();
            }
            $categoryById[$category->getId()]['__disableTmpl'] = true;
            $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
        }

        return $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'];
    }

    /**
     * Retrieve filtered list of categories id.
     *
     * @param int $storeId
     * @param string $filter
     * @return array
     * @throws LocalizedException
     */

    private function retrieveShownCategoriesIdsnew(int $storeId, $categoryIds ,string $filter = '') : array
    {
        /* @var $matchingNamesCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $matchingNamesCollection = $this->categoryCollectionFactory->create();

        $matchingNamesCollection->addAttributeToSelect('path')
            ->addAttributeToFilter('entity_id', ['in' => [$categoryIds]])
            ->setStoreId($storeId);
        $shownCategoriesIds = [];

        /** @var \Magento\Catalog\Model\Category $category */
        foreach ($matchingNamesCollection as $category) {
            foreach (explode('/', $category->getPath() ?? '') as $parentId) {
                $shownCategoriesIds[$parentId] = 1;
            }
        }

        return $shownCategoriesIds;
    }

    /**
     * Sort categories recursively based on 'label' key.
     * @param array|null 
     * @return array
     */
    protected function sortCategories(?array $categories): array
    {
        if (is_null($categories)) {
            return [];
        }

        // Use natural case-insensitive comparison for alphanumeric sorting
        usort($categories, fn($a, $b) => strnatcasecmp($a['label'], $b['label']));

        foreach ($categories as &$category) {
            if (!empty($category['optgroup']) && is_array($category['optgroup'])) {
                $category['optgroup'] = $this->sortCategories($category['optgroup']);
            }
        }

        return $categories;
    }


}