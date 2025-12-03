<?php
namespace Fedex\SelfReg\Block;

use DateInterval;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use \Magento\Customer\Model\Session;
use Magento\Directory\Model\PriceCurrency;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\UrlInterface;
use Fedex\Orderhistory\Helper\Data as OrderHistoryDataHelper;
use Fedex\Delivery\Helper\Data;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Helper\Category;
use Magento\Catalog\Model\CategoryRepository;
use Fedex\SelfReg\Helper\SelfReg;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Block\Product\ListProduct;
use Fedex\Catalog\ViewModel\ProductList;
use Fedex\CustomerGroup\Model\FolderPermission;
use Fedex\CatalogMvp\ViewModel\MvpHelper;

class EproHome extends \Magento\Framework\View\Element\Template
{

    public $_urlInterface;
    public $_productCollectionFactory;
    public $_imageHelper;
    public $_catHelper;
    const SALES_ORDER_HISTORY_URI = 'sales/order/history';

    public const EXPLORERS_NON_STANDARD_CATALOG = 'explorers_non_standard_catalog';
    public const TIGER_E424573_OPTIMIZING_PRODUCT_CARDS = 'tiger_E424573_optimizing_product_cards';

    public const TIGER_D213097_BLURRY_IMAGES_FROM_RECENTLY_ADDED_PRODUCTS = 'tiger_d213097_blurry_images_from_recently_added_products';
    public const SGC_D213762_FIX = 'sgc_d213762';

    /**
     * @param Context $context
     * @param Session $customer
     * @param CollectionFactory $orderCollectionFactory
     * @param QuoteCollectionFactory $quoteCollectionFactory
     * @param UrlInterface $urlInterface
     * @param TimezoneInterface $localeDate
     * @param OrderHistoryDataHelper $orderHistoryDataHelper
     * @param StoreManagerInterface $storeManager
     * @param Data $deliveryhelper
     * @param ProductCollectionFactory $productCollectionFactory
     * @param Image $imageHelper
     * @param Category $catHelper
     * @param CategoryRepository $categoryRepository
     * @param SelfReg $selfreghelper
     * @param Ondemand $ondemand
     * @param CatalogMvp $catalogMvpHelper
     * @param ListProduct $listProduct
     * @param ProductList $productList
     * @param FolderPermission $folderPermission
     * @param ToggleConfig $toggleConfig
     * @param MvpHelper $mvpHelper
     * @param PriceCurrency $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        public Session $customer,
        public CollectionFactory $orderCollectionFactory,
        public QuoteCollectionFactory $quoteCollectionFactory,
        UrlInterface $urlInterface,
        public TimezoneInterface $localeDate,
        public OrderHistoryDataHelper $orderHistoryDataHelper,
        public StoreManagerInterface $storeManager,
        public Data $deliveryhelper,
        ProductCollectionFactory $productCollectionFactory,
        Image $imageHelper,
        Category $catHelper,
        // B-1172285 - Custom documents tab should have the custom docs,
        public CategoryRepository $categoryRepository,
        public SelfReg $selfreghelper,
        public Ondemand $ondemand,
        protected CatalogMvp $catalogMvpHelper,
        public ListProduct $listProduct,
        public ProductList $productList,
        protected FolderPermission $folderPermission,
        protected ToggleConfig $toggleConfig,
        public MvpHelper $mvpHelper,
        protected PriceCurrency $priceCurrency,
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
     */
    public function getSubmittedOrderViewLink()
    {
        $isSelfRegCustomer = $this->selfreghelper->isSelfRegCustomer();
        /** B-1857860 */
        if ($isSelfRegCustomer || ($this->deliveryhelper->isCommercialCustomer())) {
            return $this->_urlInterface->getUrl(self::SALES_ORDER_HISTORY_URI);
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     */
    public function getQuoteViewLink()
    {
        return $this->_urlInterface->getUrl('uploadtoquote/index/quotehistory/');
    }

    /**
     * @inheritDoc
     * B-1213999 - "View Order" for completed should redirect
     * to Order History with only shipped, ready for pickup, or delivered
     *
     */
    public function getCompletedOrderViewLink()
    {

        $queryParams = [
            'advanced-filtering' => '',
            'order-status' => 'shipped;ready_for_pickup;complete',
        ];
        return $this->_urlInterface->getUrl(self::SALES_ORDER_HISTORY_URI, ['_query' => $queryParams]);

    }

    /**
     * @inheritDoc
     * B-1214000 - "View Order" for In-Progress should redirect to Order History with only In-Progress filter
     *
     */
    public function getInProgressOrderViewLink()
    {

        $queryParams = [
                'advanced-filtering' => '',
                'order-status' => 'in_process',
        ];

        return $this->_urlInterface->getUrl(self::SALES_ORDER_HISTORY_URI, ['_query' => $queryParams]);
    }

    /**
     * @inheritDoc
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function getPrintProductUrl()
    {
        $storeCategories = $this->_catHelper->getStoreCategories(false, false, true);
        if ($storeCategories) {
            foreach ($storeCategories as $category) {
                if (strpos(strtolower($category->getName()), 'print product') !== false) {
                    return $this->_catHelper->getCategoryUrl($category);
                }
            }
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function getUploadOnlyOption()
    {
		$customer = $this->deliveryhelper->getCustomer();
        $company = $this->deliveryhelper->getAssignedCompany($customer);
        if ($company && $company->getAllowOwnDocument()) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     *
     */
    public function getBrowseCatalogUrl()
    {
        $browseCatId = $this->catalogMvpHelper->getCompanySharedCatId();
        if($browseCatId) {
            $category = $this->categoryRepository->get($browseCatId);
            if(is_object($category)) {
                return $category->getUrl();
            }
        }
        $storeCategories = $this->_catHelper->getStoreCategories(false, false, true);
        if ($storeCategories) {
            foreach ($storeCategories as $category) {
                if (strpos(strtolower($category->getName()), 'browse catalog') !== false) {
                    return $this->_catHelper->getCategoryUrl($category);
                }
            }
        }
        return '#';
    }

    /**
     * @inheritDoc
     *
     * B-1213997 ePro - Home Page renders option for Catalog only
     */
    public function getCatalogOnlyOption()
    {
        $customer = $this->deliveryhelper->getCustomer();
        $company = $this->deliveryhelper->getAssignedCompany($customer);
        if ($company && $company->getAllowSharedCatalog()) {
            return true;
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     * B-1214002 - Display recently added products in shared company catalog section
     */
    public function getRecentProductCollection()
    {
        $catIds = $this->getBrowseCategoryIds();
        if ($catIds) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addCategoriesFilter(['in' => $catIds]);
            $collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
            $collection->
                addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
                
            if (!$this->toggleConfig->getToggleConfigValue('tech_titans_e_484727')) {
                $collection->addAttributeToFilter('type_id', ['neq' => 'commercial']);
            }   
           
            if ($this->deliveryhelper->isCommercialCustomer()
                && $this->catalogMvpHelper->isMvpCatalogEnabledForCompany()
            ) {
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
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'published','eq' => 1]
                    ]
                )->addAttributeToFilter(
                    [
                        ['attribute' => 'mirakl_mcm_product_id','null' => true]
                    ]
                );
            }
            if ($this->toggleConfig->getToggleConfigValue(self::EXPLORERS_NON_STANDARD_CATALOG)) {
                $collection->addAttributeToFilter(
                    [
                        ['attribute' => 'is_pending_review','neq' => 1],['attribute' => 'is_pending_review','null' => true]
                    ]
                , '', 'left');
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
     *
     *
     * @codeCoverageIgnore | resize method internally dependent on protected method
     */
    public function getProductImage($product)
    {
        if (!empty($product)) {

            if ($this->toggleConfig->getToggleConfigValue(self::TIGER_D213097_BLURRY_IMAGES_FROM_RECENTLY_ADDED_PRODUCTS)) {
                return  $this->_imageHelper->init($product, 'recently_added_products_content_widget_grid')
                    ->setImageFile($product->getSmallImage())
                    ->keepFrame(true)
                    ->getUrl();
            }

            return  $this->_imageHelper->init($product, 'new_products_content_widget_grid')
            ->setImageFile($product->getSmallImage())
            ->keepFrame(true)
            ->resize(130, 130)
            ->getUrl();
        } else {
            return $this->_imageHelper->getDefaultPlaceholderUrl('thumbnail');
        }
    }

    /**
     * @inheritDoc
     *
     *
     */
    public function getFormattedDate($date)
    {
        return $this->localeDate->date(new \DateTime($date))->format('m/d/Y');
    }

    /**
     * Get Current Company Type
     * @return string | boolean
     */
    public function getCompanyType()
    {
        $companyData = $this->customer->getOndemandCompanyInfo();
        if (is_array($companyData) && !empty($companyData) && isset($companyData['company_type'])) {
            return $companyData['company_type'];
        }
        return false;
    }

    /**
     * @inheritDoc
     *
     *
     */
    public function getAttributeSetName($attributeSetId)
    {
        return $this->deliveryhelper->getProductAttributeName($attributeSetId);
    }

    /**
     * @inheritDoc
     *
     * B-1214002 - Display recently added products in shared company catalog section
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
     * B-1214005 -  Customizable documents tab should have the customizable documents
     */
    public function getCustomDocCollection()
    {
        $catIds = $this->getBrowseCategoryIds();
        if ($catIds) {
            $collection = $this->_productCollectionFactory->create();
            $collection->addAttributeToSelect('*');
            $collection->addCategoriesFilter(['in' => $catIds]);
            $collection->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH);
            $collection->
            addAttributeToFilter('status',\Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED);
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
     * Check Folder Permission
     * @param  array $categoryIds
     * @return boolean
     */
    public function checkFolderPermission($categoryIds)
    {
        $groupId = $this->customer->getCustomer()->getGroupId();
        foreach ($categoryIds as $categoryId)
        {
            if($this->folderPermission->checkCategoryPermission($categoryId, $groupId)){
                return true;
            }
        }
        return false;
    }

    /**
     * Custom Doc Toggle
     */
    public function isCatalogMvpCustomDocEnable()
    {
        return $this->mvpHelper-> isCatalogMvpCustomDocEnable();
    }

    /**
     * @inheritDoc
     *
     * B-1411041 -  Implement self-reg header items
     */
    public function isSelfRegCompany()
    {
       return $this->selfreghelper->isSelfRegCompany();
    }

    /**
     *
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
    public function isLoaderRemovedEnable(){
        return $this->catalogMvpHelper->isLoaderRemovedEnable();
    }

    /**
     * B-1569506
     */
    public function getAddToCartUrl($proObj)
    {
	return $this->listProduct->getAddToCartUrl($proObj);
    }

    public function getTazToken()
    {
        return $this->productList->getTazToken();
    }
    public function getSiteName()
    {
        return $this->productList->getSiteName();
    }

    /**
     * @return bool
     */
    public function isTigerE424573OptimizingProductCardsEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue(
            self::TIGER_E424573_OPTIMIZING_PRODUCT_CARDS
        );
    }
    /**
     * B-1978493 - Return currency symbol to PHTML
     * @return string
     */
    public function getCurrencySymbol()
    {
        return $this->mvpHelper->getCurrencySymbol();
    }

    /**
     * B-2036526 - fixed decimal price
     * @param $amount
     * @return string
     */
    public function getFormatedPrice($amount): string
    {
        return $this->priceCurrency->convertAndFormat($amount);
    }

    /**
     * @inheritDoc
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function getUploadAndPrint()
    {
		$customer = $this->deliveryhelper->getCustomer();
        $company = $this->deliveryhelper->getAssignedCompany($customer);
        if ($company && $company->getAllowUploadAndPrint()) {
            return true;
        }
        return false;
    }

    /**
     * D-213762 - Toggle for Product Catalog Styling Fix
     *
     * @return bool
     */
    public function isProductCatalogStylingFixEnabled()
    {
		return $this->toggleConfig->getToggleConfigValue(self::SGC_D213762_FIX);
    }
}
