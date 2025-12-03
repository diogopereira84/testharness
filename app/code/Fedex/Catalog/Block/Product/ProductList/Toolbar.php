<?php

namespace Fedex\Catalog\Block\Product\ProductList;

use Magento\Catalog\Block\Product\ProductList\Toolbar as ParentToolbar;
use Magento\Catalog\Helper\Product\ProductList;
use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\ProductList\Toolbar as ToolbarModel;
use Magento\Catalog\Model\Product\ProductList\ToolbarMemorizer;
use Magento\Catalog\Model\Session;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Data\Helper\PostHelper;
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Search\Helper\Data as SearchHelper;
use Magento\Catalog\Block\Product\ListProduct;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;

class Toolbar extends ParentToolbar
{
    /**
     * Toolbar constructor.
     *
     * @param Context $context
     * @param Session $catalogSession
     * @param Config $catalogConfig
     * @param ToolbarModel $toolbarModel
     * @param EncoderInterface $urlEncoder
     * @param ProductList $productListHelper
     * @param PostHelper $postDataHelper
     * @param SearchHelper $searchHelper
     * @param ListProduct $listProduct
     * @param CatalogMvp $catalogMvp
     * @param array $data
     * @param ToolbarMemorizer|null $toolbarMemorizer
     * @param HttpContext|null $httpContext
     * @param FormKey|null $formKey
     */
    public function __construct(
        protected Context $context,
        protected Session           $catalogSession,
        protected Config            $catalogConfig,
        protected ToolbarModel      $toolbarModel,
        EncoderInterface            $urlEncoder,
        protected ProductList       $productListHelper,
        protected PostHelper        $postDataHelper,
        protected SearchHelper      $searchHelper,
        public ListProduct          $listProduct,
        protected CatalogMvp        $catalogMvp,
        array                       $data = [],
        protected ?ToolbarMemorizer $toolbarMemorizer = null,
        protected ?HttpContext      $httpContext = null,
        protected ?FormKey          $formKey = null
    ) {
        parent::__construct(
            $context,
            $catalogSession,
            $catalogConfig,
            $toolbarModel,
            $urlEncoder,
            $productListHelper,
            $postDataHelper,
            $data,
            $toolbarMemorizer,
            $httpContext,
            $formKey
        );
    }

    /**
     * @return SearchHelper
     */
    public function getSearchHelper(): SearchHelper
    {
        return $this->searchHelper;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getTotalNum()
    {
        return $this->getCollection()->getSize();
    }

     /**
     * getLoadedProductCollectionCount
     */
    public function getLoadedProductCollectionCount()
    {
        return $this->listProduct->getLoadedProductCollection()->count();
    }

    public function isMvpCatalogEnabled()
    {
        return $this->catalogMvp->isMvpSharedCatalogEnable();
    }

    /**
     * Toggle for B-2193925 Product updated at toggle
     * @return bool
     */
    public function getToggleStatusForNewProductUpdatedAtToggle()
    {
        return $this->catalogMvp->getToggleStatusForNewProductUpdatedAtToggle();
    }

    /**
     * getChildCategoryCount
     */
    public function getChildCategoryCount()
    {
        return $this->catalogMvp->getChildCategoryCount();
    }

    /**
     * B-1573026 RT-ECVS-Sorting of Catalog items for list/grid view
     * @param ProductCollection $collection
     * @return Toolbar
     */
    public function setCollection($collection): Toolbar
    {
        $this->_collection = $collection;

        $this->_collection->setCurPage($this->getCurrentPage());

        // we need to set pagination only if passed value integer and more that 0
        $limit = (int)$this->getLimit();
        if ($limit) {
            $this->_collection->setPageSize($limit);
        }
        if ($this->getCurrentOrder()) {
            if (($this->getCurrentOrder()) == 'position') {
                $this->_collection->addAttributeToSort(
                    $this->getCurrentOrder(),
                    $this->getCurrentDirection()
                );
            } else {
                if (($this->getCurrentOrder()) == 'most_recent') {
                    $this->_collection->setOrder("updated_at", "desc");
                } elseif (($this->getCurrentOrder()) == 'name_asc') {
                    $this->_collection->setOrder("name", "asc");
                } elseif (($this->getCurrentOrder()) == 'name_desc') {
                    $this->_collection->setOrder("name", "desc");
                } else {
                    $this->_collection->setOrder($this->getCurrentOrder(), $this->getCurrentDirection());
                }
            }
        }
        return $this;
    }
}
