<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Plugin\Product\Import;

use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\CategoryFactory as CategoryResourceFactory;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as AttributeSetCollectionFactory;
use Mirakl\Mci\Helper\Config;
use Fedex\CatalogMvp\Block\Adminhtml\Catalog\Product\ModelPopup;
use Magento\Catalog\Model\Product as ProductModel;
use Fedex\MarketplaceAdmin\Model\Config as ToggleSelfReg;

class Category extends \Mirakl\Mci\Helper\Product\Import\Category
{
    /**
     * @param ToggleSelfreg $toggleSelfReg
     * @param Config $config
     * @param CategoryFactory $categoryFactory
     * @param CategoryResourceFactory $categoryResourceFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param AttributeSetCollectionFactory $attrSetCollectionFactory
     * @param ModelPopup $modelPopup
     */
    public function __construct(
        private ToggleSelfreg $toggleSelfReg,
        Config $config,
        CategoryFactory $categoryFactory,
        CategoryResourceFactory $categoryResourceFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        AttributeSetCollectionFactory $attrSetCollectionFactory,
        private ModelPopup $modelPopup
    ) {
        parent::__construct(
            $config,
            $categoryFactory,
            $categoryResourceFactory,
            $categoryCollectionFactory,
            $attrSetCollectionFactory
        );
    }

    /**
     * Adds specified category to product
     *
     * @param \Mirakl\Mci\Helper\Product\Import\Category $subject
     * @param $product
     * @return mixed
     */
    public function afterAddCategoryToProduct(
        \Mirakl\Mci\Helper\Product\Import\Category $subject,
        ProductModel                               $product
    ) {
        if (!$this->toggleSelfReg->isMktSelfregEnabled()) {
            return $product;
        }

        $configuredCategory[] = $this->modelPopup->getPrintProductCategory();
        $mergedCategories     = array_merge($product->getCategoryIds(),$configuredCategory);
        $product->setCategoryIds(array_unique($mergedCategories));
        return $product;
    }
}
