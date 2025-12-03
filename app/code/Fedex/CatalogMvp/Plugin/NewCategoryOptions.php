<?php

declare(strict_types=1);

namespace Fedex\CatalogMvp\Plugin;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Ui\Component\Product\Form\Categories\Options;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\Locator\LocatorInterface;
use \Magento\Backend\Model\Session as AdminSession;
use Magento\Catalog\Model\Product;
use Psr\Log\LoggerInterface;

class NewCategoryOptions
{

    protected $categoriesTree;

    /**
     *
     * @param CatalogMvp $helper ,
     * @param CategoryCollectionFactory $categoryCollectionFactory ,
     * @param LocatorInterface $locator ,
     * @param AdminSession $adminSession ,
     * @param Product $product
     * @param LoggerInterface $logger
     */

    public function __construct(
        protected CatalogMvp $helper,
        protected CategoryCollectionFactory $categoryCollectionFactory,
        protected LocatorInterface $locator,
        protected AdminSession $adminSession,
        protected Product $product,
        private LoggerInterface $logger
    )
    {
    }

    public function aftertoOptionArray(
        Options $subject,
        $result
    ): array {

        // case to check toggle off
        if (!$this->helper->isMvpCtcAdminEnable()) {
            return $result;
        }

        if ($this->adminSession->getAttributeSetId()) {
            $prodAttributeSetId = $this->adminSession->getAttributeSetId();
            $this->adminSession->unsetAttributeSetId();
            // case to check attribute set
            $attributeSetName = $this->helper->getAttributeSetName($prodAttributeSetId);

            if ($attributeSetName != 'PrintOnDemand'){
                return $result;
            }
        }

        if ($this->adminSession->getProductId()) {
            $prodId = $this->adminSession->getProductId();
            $this->adminSession->unsetProductId();
            $podEditable = $this->helper->isProductPodEditAbleById($prodId);
            // case to check attribute set
            if (!$podEditable){
                return $result;
            }
        }

        $storeId = (int) $this->locator->getStore()->getId();

        // Get Root category for Ondemand
        $rootCategoryId = $this->helper->getRootCategoryFromStore('ondemand');

        // Get B2b Print Product category
        $B2bPrintCategory = $this->helper->getScopeConfigValue("ondemand_setting/category_setting/epro_print");

        // Get child catgories
        $categoryIds = $this->helper->getSubCategoryByParentID($rootCategoryId, $B2bPrintCategory);

        // Remove B2bprintcategory from rootcategory
        if (($key = array_search($B2bPrintCategory, $categoryIds)) !== false) {
            unset($categoryIds[$key]);
        }

        $categoriesTree = $this->getCategoriesTree($categoryIds, $storeId);


        return $categoriesTree;
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     */
    protected function getCategoriesTree($categoryIds, $storeId)
    {
        if ($this->categoriesTree === null) {
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

            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            $collection = $this->categoryCollectionFactory->create();

            $collection->addAttributeToFilter('entity_id', ['in' => array_keys($shownCategoriesIds)])
                ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
                ->setStoreId($storeId);

            $categoryById = [
                CategoryModel::TREE_ROOT_ID => [
                    'value' => CategoryModel::TREE_ROOT_ID
                ],
            ];

            foreach ($collection as $category) {
                foreach ([$category->getId(), $category->getParentId()] as $categoryId) {
                    if (!isset($categoryById[$categoryId])) {
                        $categoryById[$categoryId] = ['value' => $categoryId];
                    }
                }

                $categoryById[$category->getId()]['is_active'] = $category->getIsActive();
                $categoryById[$category->getId()]['label'] = $category->getName();
                $categoryById[$category->getParentId()]['optgroup'][] = &$categoryById[$category->getId()];
            }

            $this->categoriesTree = (isset($categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'])) ?
             $categoryById[CategoryModel::TREE_ROOT_ID]['optgroup'] : [];
        }

        return $this->categoriesTree;
    }

}
