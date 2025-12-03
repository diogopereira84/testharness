<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CatalogMvp\Block\Product;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Block\Product\ProductList\Toolbar;
use Magento\Catalog\Model\Layer;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Eav\Model\Entity\Collection\AbstractCollection;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\Helper\Data;
use Magento\Catalog\Helper\Output as OutputHelper;
use Magento\Catalog\Block\Product\Context;
use Fedex\CatalogMvp\Helper\SharedCatalogLiveSearch;
use Magento\Framework\App\ObjectManager;
use Magento\Catalog\Block\Product\ListProduct as ParentListProduct;
use Fedex\SDE\Helper\SdeHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * ListProduct Block class
 * @codeCoverageIgnore
 */
class ListProduct extends ParentListProduct
{
    protected $_defaultToolbarBlock = Toolbar::class;

    /**
     * Product Collection
     *
     * @var AbstractCollection
     */
    protected $_productCollection;

    /**
     * Catalog layer
     *
     * @var Layer
     */
    protected $_catalogLayer;

    /**
     * @var PostHelper
     */
    protected $_postDataHelper;

    /**
     * @var Data
     */
    protected $urlHelper;

    /**
     * @param Context                     $context
     * @param PostHelper                  $postDataHelper
     * @param Resolver                    $layerResolver
     * @param CategoryRepositoryInterface $categoryRepository
     * @param Data                        $urlHelper
     * @param SharedCatalogLiveSearch     $sharedCatalogLiveSearch
     * @param array                       $data
     * @param OutputHelper|null           $outputHelper
     * @param SdeHelper                   $SdeHelper
     * @param ToggleConfig                $toggleConfig
     */
    public function __construct(
        Context $context,
        PostHelper $postDataHelper,
        Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        Data $urlHelper,
        protected SharedCatalogLiveSearch $sharedCatalogLiveSearch,
        protected SdeHelper $sdeHelper,
        protected ToggleConfig $toggleConfig,
        public CatalogMvp $mvpHelper,
        array $data = [],
        ?OutputHelper $outputHelper = null
    ) {
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data,
            $outputHelper
        );
    }   

    /**
     * Get Product Collection
     *
     * @return object
     */
    protected function _getProductCollection()
    {
        if ($this->toggleConfig->getToggleConfigValue('print_product_prod_issue_fixed')) {
            if($this->sharedCatalogLiveSearch->isEnabledCatalogPerformance() && !$this->sdeHelper->getIsSdeStore()){
                if($this->sharedCatalogLiveSearch->getToggleStatusForPerformanceImprovmentPhasetwo()) {
                    if($this->mvpHelper->isSharedCatalogPage()) {
                        static $productCollection;
                        $productCollection = $this->sharedCatalogLiveSearch->getProductCollection();
                    } else {
                        static $productCollection;
                        if ($productCollection === null) {
                            $productCollection = $this->sharedCatalogLiveSearch->getProductCollection();
                        }
                    }
                    return $productCollection;
                    } else {
                        if($this->mvpHelper->isSharedCatalogPage()) {
                            $this->_productCollection = $this->sharedCatalogLiveSearch->getProductCollection();
                        } else {
                            if ($this->_productCollection === null) {
                                $this->_productCollection = $this->sharedCatalogLiveSearch->getProductCollection();
                            }
                        }
                        return $this->_productCollection;
                    }
                } else {
                    return parent::_getProductCollection();
            }
        } else {
            if ($this->_productCollection === null) {
                $this->_productCollection = $this->sharedCatalogLiveSearch->getProductCollection();
            }
            return $this->_productCollection;
        }   
   
    }
  /**
     * Get _prepareLayout
     *
     * @return object
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();
        if($this->mvpHelper->getMergedSharedCatalogFilesToggle() && $this->mvpHelper->isMvpCatalogEnabledForCompany()) {
            $this->setTemplate('Magento_Catalog::product/product-category-list-customer-merged.phtml');
        } else {
            if ( $this->sharedCatalogLiveSearch->getToggleStatusCustomerPerformanceImprovmentPhaseOne() && !$this->sdeHelper->getIsSdeStore()) {
                $this->setTemplate('Magento_Catalog::product/product-category-list-customer.phtml');
            } else if ($this->sharedCatalogLiveSearch->getToggleStatusCustomerAdminPerformanceImprovmentPhaseOne() && !$this->sdeHelper->getIsSdeStore()) {
                $this->setTemplate('Magento_Catalog::product/product-category-list-customer-admin.phtml');
            }
        }
        return $this;
    }
}
