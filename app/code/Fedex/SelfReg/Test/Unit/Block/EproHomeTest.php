<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SelfReg\Test\Unit\Block;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Block\EproHome;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Collection as QuoteCollection;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Magento\Catalog\Block\Product\ListProduct;
use Magento\Catalog\Model\Product;
use Fedex\Catalog\ViewModel\ProductList;
use Fedex\CustomerGroup\Model\FolderPermission;
use Fedex\CatalogMvp\ViewModel\MvpHelper;
use const Magento\Directory\Model\PriceCurrency;
use PHPUnit\Framework\MockObject\MockObject;
class EproHomeTest extends TestCase
{
    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $customerSession;
    /**
     * @var (\Magento\Sales\Model\ResourceModel\Order\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderCollectionFactory;
    /**
     * @var (\Magento\Sales\Model\ResourceModel\Order\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderCollection;
    /**
     * @var (\Magento\Quote\Model\ResourceModel\Quote\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteCollectionFactory;
    /**
     * @var (\Magento\Quote\Model\ResourceModel\Quote\Collection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $quoteCollection;
    /**
     * @var (\Magento\Sales\Model\Order & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $order;
    protected $urlInterface;
    protected $toggleConfig;
    protected $localeDate;
    protected $orderHistoryDataHelper;
    protected $storeManagerInterfaceMock;
    protected $storeMock;
    protected $deliveryHelperMock;
    protected $custonerRepositoryMock;
    protected $productCollection;
    protected $productCollectionFactory;
    protected $imageHelperMock;
    protected $categoryHelperMock;
    protected $catCollectionMock;
    protected $categoryMock;
    protected $categoryRepositoryMock;
    protected $selfreghelper;
    protected $ondemand;
    protected $catalogMvpMock;
    protected $listProductMock;
    protected $productMock;
    protected $productList;
    protected $folderPermission;
    protected $mvpHelper;
    protected $priceCurrencyMock;
    protected $home;
    private MockObject|CompanyInterface $companyMock;

    /**
     * @inheritDoc
     * B-1145896
     */
    protected function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getId', 'getCustomer', 'getGroupId','getOndemandCompanyInfo'])
            ->getMock();

        $this->orderCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->orderCollection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count','addFieldToSelect','getColumnValues'])
            ->getMock();

        $this->quoteCollectionFactory = $this->getMockBuilder(QuoteCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->quoteCollection = $this->getMockBuilder(QuoteCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'count'])
            ->getMock();

        $this->order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->urlInterface = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->localeDate = $this->getMockBuilder(TimezoneInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['date', 'convertConfigTimeToUtc', 'format', 'sub'])
            ->getMockForAbstractClass();

        //B-1145903 - Show Order History with only shipped, ready for pickup or delivered
        $this->orderHistoryDataHelper = $this->getMockBuilder(\Fedex\Orderhistory\Helper\Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['isSDEHomepageEnable'])
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(\Magento\Store\Model\Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBaseUrl'])
            ->getMock();

        $this->deliveryHelperMock = $this
            ->getMockBuilder(DeliveryHelper::class)
            ->setMethods(['getCustomer', 'getAssignedCompany', 'getProductAttributeName','isCommercialCustomer'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->custonerRepositoryMock = $this
            ->getMockBuilder(CustomerInterface::class)
            ->setMethods(['getEnableUploadSection','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productCollection = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter', 'addAttributeToSelect',
            'addAttributeToSort', 'setPageSize', 'addCategoriesFilter'])
            ->getMock();

        $this->productCollectionFactory =
        $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->imageHelperMock = $this->getMockBuilder(\Magento\Catalog\Helper\Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl','init','setImageFile','keepFrame','resize'])
            ->getMock();

        // B-1172285 - Custom documents tab should have the custom docs
        $this->categoryHelperMock = $this->getMockBuilder(\Magento\Catalog\Helper\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreCategories', 'getCategoryUrl'])
            ->getMock();

        $this->catCollectionMock =
        $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter', 'getFirstItem', 'getAllIds','getIterator'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getPath','getName','getAllChildren', 'getRequestPath'])
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(\Magento\Catalog\Model\CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->selfreghelper = $this
            ->getMockBuilder(\Fedex\SelfReg\Helper\SelfReg::class)
            ->setMethods(['isSelfRegCompany','isSelfRegCustomer'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->ondemand = $this
            ->getMockBuilder(Ondemand::class)
            ->setMethods(['getPrintProductCategory'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->catalogMvpMock = $this
            ->getMockBuilder(CatalogMvp::class)
            ->setMethods(['isMvpSharedCatalogEnable','isMvpCustomerAdminEnable','isEproHomepageEnabled','isMvpCatalogEnabledForCompany','getCompanySharedCatId','isLoaderRemovedEnable'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->listProductMock = $this
            ->getMockBuilder(ListProduct::class)
            ->setMethods(['getAddToCartUrl'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->productMock = $this
            ->getMockBuilder(Product::class)
            ->setMethods(['load'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->productList = $this
            ->getMockBuilder(ProductList::class)
            ->setMethods(['getTazToken','getSiteName'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->folderPermission = $this
            ->getMockBuilder(FolderPermission::class)
            ->setMethods(['checkCategoryPermission'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->mvpHelper = $this
            ->getMockBuilder(MvpHelper::class)
            ->setMethods(['isCatalogMvpCustomDocEnable'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->priceCurrencyMock = $this->getMockBuilder(\Magento\Directory\Model\PriceCurrency::class)
            ->setMethods(['convertAndFormat'])
            ->disableOriginalConstructor()
            ->getMock();


        $this->home = new EproHome(
            $this->context,
            $this->customerSession,
            $this->orderCollectionFactory,
            $this->quoteCollectionFactory,
            $this->urlInterface,
            $this->localeDate,
            $this->orderHistoryDataHelper,
            $this->storeManagerInterfaceMock,
            $this->deliveryHelperMock,
            $this->productCollectionFactory,
            $this->imageHelperMock,
            $this->categoryHelperMock,
            $this->categoryRepositoryMock,
            $this->selfreghelper,
            $this->ondemand,
            $this->catalogMvpMock,
            $this->listProductMock,
            $this->productList,
            $this->folderPermission,
            $this->toggleConfig,
            $this->mvpHelper,
            $this->priceCurrencyMock
        );
    }

    /**
     * @inheritDoc
     * B-1214001
     */
    public function testGetSubmittedOrderViewLinkWithoutToggle()
    {
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(0);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(0);
        /** B-1857860 */

        $this->assertEquals('#', $this->home->getSubmittedOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1214001
     */
    public function testGetSubmittedOrderViewLink()
    {
        $expectedUrl = "https://staging3.office.fedex.com/sales/order/history";
        $this->selfreghelper->expects($this->any())->method('isSelfRegCustomer')->willReturn(1);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(1);

        /**B-1857860 */

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->home->getSubmittedOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1145903 - Show Order History with only shipped, ready for pickup or delivered
     */
    public function testGetCompletedOrderViewLink()
    {
        $expectedUrl = "https://staging3.office.fedex.com/sde_default/sales/order/history/
        ?advanced-filtering=&order-status=shipped%3Bready_for_pickup%3Bdelivered";

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->home->getCompletedOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1214000 - ePro - "View Order" for In-Progress should redirect to Order History with only In-Progress filter
     */
    public function testGetInProgressOrderViewLink()
    {
        $expectedUrl = "https://staging3.office.fedex.com/sde_default/sales/order/history/
        ?advanced-filtering=&order-status=in_process";

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->home->getInProgressOrderViewLink());
    }


    /**
     * Test for GetPrintProductUrl.
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function testGetPrintProductUrl()
    {
        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->categoryHelperMock->expects($this->any())
            ->method('getCategoryUrl')
            ->willReturn('https://staging3.office.fedex.com/l6site51/b2b-print-products.html');

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Print Product');

        $this->categoryMock->expects($this->any())
            ->method('getRequestPath')
            ->willReturn('b2b-print-products.html');

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')
        ->willReturn('https://staging3.office.fedex.com/l6site51/');

        $this->ondemand->expects($this->any())
            ->method('getPrintProductCategory')
            ->willReturn(true);

        $this->assertEquals('https://staging3.office.fedex.com/l6site51/b2b-print-products.html',
        $this->home->getPrintProductUrl());
    }
    /**
     * Test for GetPrintProductUrl for False statement.
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function testGetPrintProductUrlWithFalse()
    {
        $expectedResult = '#';
        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($expectedResult);
        $this->ondemand->expects($this->any())
            ->method('getPrintProductCategory')
            ->willReturn(true);

        $this->assertEquals($expectedResult, $this->home->getPrintProductUrl());
    }

    /**
     * Test for getUploadOnlyOption.
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function testGetUploadOnlyOption()
    {
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)
        ->will($this->returnValue($this->companyMock));
        $this->companyMock->expects($this->any())->method('getAllowOwnDocument')->willReturn(1);
        $this->assertEquals(true, $this->home->getUploadOnlyOption());

    }
    /**
     * Test for getUploadOnlyOption.with False Condition
     *
     * B-1214009 - ePro-Home Page renders only option for Upload only
     */
    public function testGetUploadOnlyOptionForNoCompany()
    {
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
        $this->companyMock->expects($this->any())->method('getAllowOwnDocument')->willReturn(false);
        $this->assertEquals(false, $this->home->getUploadOnlyOption());
    }

    /**
     * Test for getBrowseCatalogUrl.
     *
     * B-1160235
     */
    public function testGetBrowseCatalogUrl()
    {
        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->categoryHelperMock->expects($this->any())
            ->method('getCategoryUrl')
            ->willReturn('https://staging3.office.fedex.com/l6site51/browse-catalog.html');

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getRequestPath')
            ->willReturn('b2b-print-products.html');

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->assertEquals('https://staging3.office.fedex.com/l6site51/browse-catalog.html',
        $this->home->getBrowseCatalogUrl());
    }

    /**
     * @test testGetBrowseCatalogUrlWithFalse
     */
    public function testGetBrowseCatalogUrlWithFalse()
    {
        $expectedResult = '#';
        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn(false);

        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);

        $this->assertEquals($expectedResult, $this->home->getBrowseCatalogUrl());
    }


    /**
     * Test for getCatalogOnlyOption.
     *
     * B-1145888
     */
    public function testGetCatalogOnlyOption()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getEnableUploadSection','getEnableCatalogSection','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)
        ->will($this->returnValue($this->companyMock));
        $this->companyMock->expects($this->any())->method('getAllowSharedCatalog')->willReturn(1);
        $this->assertEquals(true, $this->home->getCatalogOnlyOption());

    }
    /**
     * Test for getCatalogOnlyOption.with No company Assigned
     *
     * B-1145888
     */
    public function testGetCatalogOnlyOptionForNoCompany()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getEnableUploadSection','getEnableCatalogSection','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
        $this->companyMock->expects($this->any())->method('getAllowSharedCatalog')->willReturn(1);
        $this->assertEquals(false, $this->home->getCatalogOnlyOption());

    }

    /**
     * Test for getCatalogOnlyOption for Not a SDE store
     *
     * B-1145888
     */
    public function testGetCatalogOnlyOptionForNNoSDEStore()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(0);
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getEnableUploadSection','getEnableCatalogSection','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
        $this->companyMock->expects($this->any())->method('getAllowSharedCatalog')->willReturn(1);
        $this->assertEquals(false, $this->home->getCatalogOnlyOption());

    }

    /**
     * Test for getRecentProductCollection
     *
     * B-1160241
     */
    public function testGetRecentProductCollection()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCatalogEnabledForCompany')
            ->willReturn(true);

        $this->toggleConfig->expects($this->exactly(2))
            ->method('getToggleConfigValue')
            ->withConsecutive(
                ['tech_titans_e_484727'],
                [EproHome::EXPLORERS_NON_STANDARD_CATALOG]
            )
            ->willReturnOnConsecutiveCalls(false, true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn(['440']);

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSort')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('setPageSize')
            ->willReturn($this->productCollection);

        $this->assertEquals($this->productCollection, $this->home->getRecentProductCollection());
    }

    /**
     * Test for getRecentProductCollectionForSharedCatId
     *
     */
    public function testGetRecentProductCollectionForSharedCatId()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(0);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn(['440']);

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSort')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('setPageSize')
            ->willReturn($this->productCollection);

        $this->assertEquals($this->productCollection, $this->home->getRecentProductCollection());
    }

    /**
     * Test for getRecentProductCollectionForSharedCatIdWithoutBrowseCat
     *
     */
    public function testGetRecentProductCollectionForSharedCatIdWithoutBrowseCat()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(440);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('MG');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn(['440']);

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSort')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('setPageSize')
            ->willReturn($this->productCollection);

        $this->assertEquals($this->productCollection, $this->home->getRecentProductCollection());
    }

    /**
     * Test for getRecentProductCollectionForSharedCatIdWithoutCatId
     *
     */
    public function testGetRecentProductCollectionForSharedCatIdWithoutCatId()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(0);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('MG');

        $this->assertEquals("", $this->home->getRecentProductCollection());
    }

    /**
     * Test for getRecentProductCollection
     *
     * B-1160241
     */
    public function testGetRecentProductCollectionWithFalse()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCatalogEnabledForCompany')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn([]);

        $this->assertEquals(false, $this->home->getRecentProductCollection());
    }

    /**
     * Test for getFormattedDate
     *
     * B-1160241
     */
    public function testGetFormattedDate()
    {
        $date = date('m/d/Y');
        $this->localeDate->expects($this->any())->method('date')->willReturn(new \DateTime());
        $this->localeDate->expects($this->any())->method('format')->willReturnSelf();
        $this->assertEquals($date, $this->home->getFormattedDate($date));
    }

    /**
     * Test for getAttributeSetName
     *
     * B-1160241
     */
    public function testGetAttributeSetName()
    {
        $attributeSetId = 1;
        $this->deliveryHelperMock->expects($this->any())
        ->method('getProductAttributeName')->with($attributeSetId)->willReturn('PrintOnDemand');
        $this->assertEquals('PrintOnDemand', $this->home->getAttributeSetName($attributeSetId));
    }

    /**
     * Test for getAttributeSetName
     *
     * B-1172285 - Custom documents tab should have the custom docs
     */
    public function testGetCustomDocCollection()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(12);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn(['440']);

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSort')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('setPageSize')
            ->willReturn($this->productCollection);

        $this->assertEquals($this->productCollection, $this->home->getCustomDocCollection());
    }

    /**
     * testGetCustomDocCollectionForSharedCatId
     *
     */
    public function testGetCustomDocCollectionForSharedCatId()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(0);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn(['440']);

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSort')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('setPageSize')
            ->willReturn($this->productCollection);

        $this->assertEquals($this->productCollection, $this->home->getCustomDocCollection());
    }

    /**
     * testGetCustomDocCollectionForSharedCatIdWithoutBrowseCat
     *
     */
    public function testGetCustomDocCollectionForSharedCatIdWithoutBrowseCat()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(440);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('MG');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn(['440']);

        $this->productCollectionFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSelect')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addCategoriesFilter')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('addAttributeToSort')
            ->willReturn($this->productCollection);

        $this->productCollection->expects($this->any())
            ->method('setPageSize')
            ->willReturn($this->productCollection);

        $this->assertEquals($this->productCollection, $this->home->getCustomDocCollection());
    }

    /**
     * testGetCustomDocCollectionForSharedCatIdWithoutCatId
     *
     */
    public function testGetCustomDocCollectionForSharedCatIdWithoutCatId()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catalogMvpMock->expects($this->any())
            ->method('getCompanySharedCatId')
            ->willReturn(0);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('MG');

        $this->assertEquals("", $this->home->getCustomDocCollection());
    }

    /**
     * Test for getAttributeSetName
     *
     * B-1172285 - Custom documents tab should have the custom docs
     */
    public function testGetCustomDocItemsForFalseReturn()
    {
        $this->deliveryHelperMock->expects($this->any())
        ->method('isCommercialCustomer')
        ->willReturn(true);

        $this->catalogMvpMock->expects($this->any())
            ->method('isMvpCustomerAdminEnable')
            ->willReturn(true);

        $this->categoryHelperMock->expects($this->any())
            ->method('getStoreCategories')
            ->willReturn($this->catCollectionMock);

        $this->catCollectionMock->expects($this->any())->method('getIterator')
            ->willReturn(new \ArrayIterator([$this->categoryMock]));

        $this->categoryMock->expects($this->any())
            ->method('getName')
            ->willReturn('Browse Catalog');

        $this->categoryMock->expects($this->any())
            ->method('getId')
            ->willReturn(440);

        $this->categoryRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($this->categoryMock);

        $this->categoryMock->expects($this->any())
            ->method('getAllChildren')
            ->willReturn([]);

        $this->assertEquals(false, $this->home->getCustomDocCollection());
    }

     /**
     * @inheritDoc
     * B-1238292
     */
    public function testGetQuoteViewLink()
    {
        $expectedUrl = "https://staging3.office.fedex.com/negotiable_quote/quote/";

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->home->getQuoteViewLink());
    }

     /**
     * @inheritDoc
     */
    public function testIsSelfRegCompany()
    {

        $this->selfreghelper->expects($this->any())
        ->method('isSelfRegCompany')
        ->willReturn(true);

        $this->assertEquals(true, $this->home->isSelfRegCompany());
    }

    /**
     * test isCatalogMvpCustomDocEnable
     */
    public function testIsCatalogMvpCustomDocEnable()
    {
        $this->mvpHelper->expects($this->any())
        ->method('isCatalogMvpCustomDocEnable')
        ->willReturn(true);

        $this->assertEquals(true,$this->home->isCatalogMvpCustomDocEnable());
    }
    /**
     * testisMvpCatalogEnble
     */
    public function testisMvpCatalogEnble()
    {
        $this->catalogMvpMock->expects($this->any())
        ->method('isMvpSharedCatalogEnable')
        ->willReturn(true);
        $this->assertEquals(true, $this->home->isMvpCatalogEnble());
    }

     /**
     * testisLoaderRemovedEnable
     */
    public function testisLoaderRemovedEnable()
    {
        $this->catalogMvpMock->expects($this->any())
        ->method('isLoaderRemovedEnable')
        ->willReturn(true);
    }


    /**
     * testisMvpCatalogEnble
     */
    public function testGetAddToCartUrl()
    {
        $url = 'add_to_cart_link';
        $this->productMock->expects($this->any())->method('load')->willReturnSelf();
        $this->listProductMock->expects($this->any())->method('getAddToCartUrl')->willReturn($url);
        $this->assertIsString($this->home->getAddToCartUrl($this->productMock));
    }

    /**
     * Test Case for getTazToken
     */
    public function testGetTazToken()
    {
        $this->productList->expects($this->any())
        ->method('getTazToken')
        ->willReturn("TazToken");
        $this->assertEquals("TazToken", $this->home->getTazToken());
    }

    /**
     * Test Case for getSiteName
     */
    public function testGetSiteName()
    {
        $this->productList->expects($this->any())
        ->method('getSiteName')
        ->willReturn("l6site51");
        $this->assertEquals("l6site51", $this->home->getSiteName());
    }

    /**
     * Test Check Folder permissions
     */
    public function testCheckFolderPermission()
    {
        $this->customerSession->expects($this->any())
        ->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())
        ->method('getGroupId')->willReturn(1);
        $categoryIds = [1,2];
        $this->folderPermission->expects($this->any())
        ->method('checkCategoryPermission')->willReturn(1);
        $this->assertEquals(true,$this->home->checkFolderPermission($categoryIds));

    }

    /**
     * Test Check Folder permissions with false
     */
    public function testCheckFolderPermissionwithFalse()
    {
        $this->customerSession->expects($this->any())
        ->method('getCustomer')->willReturnSelf();
        $this->customerSession->expects($this->any())
        ->method('getGroupId')->willReturn(1);
        $categoryIds = [1,2];
        $this->folderPermission->expects($this->any())
        ->method('checkCategoryPermission')->willReturn(0);
        $this->assertEquals(false,$this->home->checkFolderPermission($categoryIds));

    }

    /**
     * Test Case for getCompanyType
     */
    public function testGetCompanyType()
    {
        $companyData = [];
        $companyData['company_type'] = "epro";
        $this->customerSession->expects($this->any())
            ->method('getOndemandCompanyInfo')->willReturn($companyData);
        $this->assertEquals("epro", $this->home->getCompanyType());
    }


    /**
     * Test Case for getProductImage
     */
    public function testGetProductImage()
    {
        $this->imageHelperMock->expects($this->any())
            ->method('init')->willReturn($this->imageHelperMock);
        $this->imageHelperMock->expects($this->any())
        ->method('setImageFile')->willReturn($this->imageHelperMock);
        $this->imageHelperMock->expects($this->any())
        ->method('keepFrame')->willReturn($this->imageHelperMock);
        $this->imageHelperMock->expects($this->any())
        ->method('resize')->willReturn($this->imageHelperMock);
        $this->imageHelperMock->expects($this->any())
        ->method('getUrl')->willReturn("https://www.test.com/test.jpg");
        $this->assertEquals("https://www.test.com/test.jpg", $this->home->getProductImage($this->productMock));
    }

    public function testGetFormatedPrice()
    {
        $value = 10.00;

        $expectedFormatedPrice = '$' . number_format($value, 2);

        $this->priceCurrencyMock->method('convertAndFormat')
            ->with($value)
            ->willReturn($expectedFormatedPrice);

        $actualFormatedPrice = $this->home->getFormatedPrice($value);

        $this->assertEquals($expectedFormatedPrice, $actualFormatedPrice);
    }

    public function testgetUploadAndPrintTrue()
    {
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument', 'getAllowUploadAndPrint'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)
        ->will($this->returnValue($this->companyMock));
        $this->companyMock->expects($this->any())->method('getAllowUploadAndPrint')->willReturn(1);
        $this->assertEquals(true, $this->home->getUploadAndPrint());

    }

    public function testgetUploadAndPrintFalse()
    {
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument', 'getAllowUploadAndPrint'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())
        ->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())
        ->method('getAssignedCompany')->with($this->custonerRepositoryMock)
        ->will($this->returnValue($this->companyMock));
        $this->companyMock->expects($this->any())->method('getAllowUploadAndPrint')->willReturn(0);
        $this->assertEquals(false, $this->home->getUploadAndPrint());

    }
}
