<?php
namespace Fedex\SelfReg\Block;

use DateInterval;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Fedex\Orderhistory\Helper\Data as OrderHistoryHelper;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Fedex\SelfReg\Block\EproHome;
use Fedex\CatalogMvp\Helper\CatalogMvp;

/**
 * Class home is responsible for the contents rendered in SDE homepage
 */
class Home extends Template
{
    public $_urlInterface;
    public $_productCollectionFactory;
    public $_imageHelper;
    public $_catHelper;
    const SALES_ORDER_HISTORY_URI = 'sales/order/history';
    /**
     * Home Contructor.
     *
     * B-1145896
     * B-1255699 Removed unused methods and done the phpcs fixes
     *
     * @param Context               $context
     * @param UrlInterface          $urlInterface
     * @param ToggleConfig          $toggleConfig
     * @param TimezoneInterface     $localeDate
     * @param orderHistoryHelper    $orderHistoryDataHelper
     * @param StoreManagerInterface $storeManager
     * @param DeliveryHelper        $deliveryhelper
     * @param CollectionFactory     $productCollectionFactory
     * @param Image                 $imageHelper
     * @param Category              $catHelper
     * @param CategoryRepository    $categoryRepository
     * @param EproHome              $eproHome
     * @param array                 $data
     * @return void
     */
    public function __construct(
        Context $context,
        UrlInterface $urlInterface,
        public ToggleConfig $toggleConfig,
        public TimezoneInterface $localeDate,
        public orderHistoryHelper $orderHistoryDataHelper,
        public StoreManagerInterface $storeManager,
        public DeliveryHelper $deliveryhelper,
        CollectionFactory $productCollectionFactory,
        Image $imageHelper,
        Category $catHelper,
        // B-1172285 - Custom documents tab should have the custom docs,
        public CategoryRepository $categoryRepository,
        public EproHome $eproHome,
        protected CatalogMvp $catalogMvpHelper,
        array $data = []
    ) {
        $this->_urlInterface = $urlInterface;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_imageHelper = $imageHelper;
        $this->_catHelper = $catHelper;
        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     *
     * B-1145896
     */
    public function getSubmittedOrderViewLink()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            return $this->_urlInterface->getUrl(self::SALES_ORDER_HISTORY_URI);
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1145903 - Show Order History with only shipped, ready for pickup or delivered
     */
    public function getCompletedOrderViewLink()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            $queryParams = [
                'advanced-filtering' => '',
                'order-status' => 'shipped;ready_for_pickup;complete',
            ];

            return $this->_urlInterface->getUrl(self::SALES_ORDER_HISTORY_URI, ['_query' => $queryParams]);
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1145900 - View Order  for In-Progress
     */
    public function getInProgressOrderViewLink()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            $queryParams = [
                'advanced-filtering' => '',
                'order-status' => 'in_process',
            ];

            return $this->_urlInterface->getUrl(self::SALES_ORDER_HISTORY_URI, ['_query' => $queryParams]);
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1145888:Home Page renders only option for Upload only
     */
    public function getPrintProductUrl()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            return $this->eproHome->getPrintProductUrl();
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1145888:Home Page renders only option for Upload only
     */
    public function getUploadOnlyOption()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            $customer = $this->deliveryhelper->getCustomer();
            $company = $this->deliveryhelper->getAssignedCompany($customer);
            if ($company && $company->getAllowOwnDocument() == '1') {
                return true;
            }
            return false;
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1160235:Home Page renders only option for Catalog only
     */
    public function getBrowseCatalogUrl()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            return $this->eproHome->getBrowseCatalogUrl();
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1160235:Home Page renders only option for Catalog only
     */
    public function getCatalogOnlyOption()
    {
        if ($this->orderHistoryDataHelper->isSDEHomepageEnable()) {
            $customer = $this->deliveryhelper->getCustomer();
            $company = $this->deliveryhelper->getAssignedCompany($customer);
            if ($company && $company->getAllowSharedCatalog()) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1160241:Display recently added products in shared company catalog section
     */
    public function getRecentProductCollection()
    {
        $catIds = $this->getBrowseCategoryIds();
        if ($catIds) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addCategoriesFilter(['in' => $catIds]);
            $collection->addAttributeToFilter('visibility', Visibility::VISIBILITY_BOTH);
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            if ($this->deliveryhelper->isCommercialCustomer()) {
                $date = date('Y-m-d H:i:s');
                $collection->addAttributeToFilter(
                    [
                        ['attribute' => 'end_date_pod','null' => true],
                        ['attribute' => 'end_date_pod','gteq' => $date],
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'start_date_pod','null' => true],
                        ['attribute' => 'start_date_pod','lteq' => $date],
                    ]
                );
            }
            $collection->addAttributeToSort('entity_id', 'desc');
            $collection->setPageSize(5);
            return $collection;
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1160241:Display recently added products in shared company catalog section
     *
     * @codeCoverageIgnore | resize method internally dependent on protected method
     */
    public function getProductImage($product)
    {
        if (!empty($product)) {
            return $this->_imageHelper->init($product, 'new_products_content_widget_grid')
                ->setImageFile($product->getSmallImage())
                ->keepFrame(true)
                ->resize(140, 160)
                ->getUrl();
        } else {
            return $this->_imageHelper->getDefaultPlaceholderUrl('thumbnail');
        }
    }

    /**
     * @inheritDoc
     *
     * B-1160241:Display recently added products in shared company catalog section
     */
    public function getFormattedDate($date)
    {
        return $this->localeDate->date(new \DateTime($date))->format('m/d/Y');
    }

    /**
     * @inheritDoc
     *
     * B-1160241:Display recently added products in shared company catalog section
     */
    public function getAttributeSetName($attributeSetId)
    {
        return $this->deliveryhelper->getProductAttributeName($attributeSetId);
    }

    /**
     * @inheritDoc
     *
     * B-1160241:Display Browse Category ids
     */
    public function getBrowseCategoryIds()
    {
        $browseCatIds = [];
        $browseCatId = $this->catalogMvpHelper->getCompanySharedCatId();
        if ($browseCatId) {
            $categoryRep = $this->categoryRepository->get($browseCatId);
            $browseCatIds = $categoryRep->getAllChildren(true);
        } else {
            $storeCategories = $this->_catHelper->getStoreCategories(false, false, true);
            if ($storeCategories) {
                foreach ($storeCategories as $category) {
                    if (strpos(strtolower($category->getName()), 'browse catalog') !== false) {
                        $categoryRep = $this->categoryRepository->get($category->getId());
                        $browseCatIds = $categoryRep->getAllChildren(true);
                    }
                }
            }
        }

        return $browseCatIds;
    }

    /**
     * @inheritDoc
     *
     * B-1172285 - Custom documents tab should have the custom docs
     */
    public function getCustomDocCollection()
    {
        $catIds = $this->getBrowseCategoryIds();
        if ($catIds) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addCategoriesFilter(['in' => $catIds]);
            $collection->addAttributeToFilter('visibility', Visibility::VISIBILITY_BOTH);
            $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
            $collection->addAttributeToFilter('customizable', 1);
            if ($this->deliveryhelper->isCommercialCustomer()) {
                $date = date('Y-m-d H:i:s');
                $collection->addAttributeToFilter(
                    [
                        ['attribute' => 'end_date_pod','null' => true],
                        ['attribute' => 'end_date_pod','gteq' => $date],
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'start_date_pod','null' => true],
                        ['attribute' => 'start_date_pod','lteq' => $date],
                    ]
                );
            }
            $collection->addAttributeToSort('entity_id', 'desc');
            $collection->setPageSize(5);
            return $collection;
        }

        return false;
    }
 /**
     *
     * B-1569412 -  Update shared catalog
     */

     public function isMvpCatalogEnble()
     {
         return $this->catalogMvpHelper->isMvpSharedCatalogEnable();
     }

    /**
     * Tech Titans - Bugfix spinner
     * @return bool
     */
    public function isLoaderRemovedEnable(): bool
    {
        return (bool) $this->catalogMvpHelper->isLoaderRemovedEnable();
    }

}
