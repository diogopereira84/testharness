<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SelfReg\Test\Unit\Block;

use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Block\Home;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Block\EproHome;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use PHPUnit\Framework\MockObject\MockObject;

class HomeTest extends TestCase
{

    /**
     * @var (\Magento\Framework\View\Element\Template\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    protected $eproHomeMock;
    protected $urlInterface;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $localeDate;
    protected $orderHistoryDataHelper;
    protected $storeManagerInterfaceMock;
    protected $storeMock;
    protected $deliveryHelperMock;
    protected $custonerRepositoryMock;
    protected $productCollection;
    protected $productCollectionFactory;
    /**
     * @var (\Magento\Catalog\Helper\Image & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $imageHelperMock;
    protected $categoryHelperMock;
    protected $catCollectionMock;
    protected $categoryMock;
    protected $categoryRepositoryMock;
    protected $catalogMvpMock;
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

        $this->eproHomeMock = $this->getMockBuilder(EproHome::class)
            ->disableOriginalConstructor()
            ->setMethods(['getBrowseCatalogUrl','getPrintProductUrl'])
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
            ->setMethods(['getAllowOwnDocument'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->productCollection = $this->getMockBuilder(ProductCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter', 'addAttributeToSelect', 'addAttributeToSort', 'setPageSize', 'addCategoriesFilter'])
            ->getMock();

        $this->productCollectionFactory = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->imageHelperMock = $this->getMockBuilder(\Magento\Catalog\Helper\Image::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMock();

        // B-1172285 - Custom documents tab should have the custom docs
        $this->categoryHelperMock = $this->getMockBuilder(\Magento\Catalog\Helper\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreCategories'])
            ->getMock();

        $this->catCollectionMock = $this->getMockBuilder(\Magento\Catalog\Model\ResourceModel\Category\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addAttributeToFilter', 'getFirstItem', 'getAllIds','getIterator'])
            ->getMock();

        $this->categoryMock = $this->getMockBuilder(\Magento\Catalog\Model\Category::class)
            ->disableOriginalConstructor()
            ->setMethods(['getId', 'getPath','getName','getAllChildren'])
            ->getMock();

        $this->categoryRepositoryMock = $this->getMockBuilder(\Magento\Catalog\Model\CategoryRepository::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMock();

        $this->catalogMvpMock = $this
            ->getMockBuilder(CatalogMvp::class)
            ->setMethods(['isMvpSharedCatalogEnable','isMvpCustomerAdminEnable','getCompanySharedCatId','isLoaderRemovedEnable'])
            ->disableOriginalConstructor()
            ->getMock();


        $this->home = new Home(
            $this->context,
            $this->urlInterface,
            $this->toggleConfig,
            $this->localeDate,
            $this->orderHistoryDataHelper,
            $this->storeManagerInterfaceMock,
            $this->deliveryHelperMock,
            $this->productCollectionFactory,
            $this->imageHelperMock,
            $this->categoryHelperMock,
            $this->categoryRepositoryMock,
            $this->eproHomeMock,
            $this->catalogMvpMock
        );
    }

    /**
     * @inheritDoc
     * B-1145896
     */
    public function testGetSubmittedOrderViewLinkWithoutToggle()
    {
        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(0);

        $this->assertEquals('#', $this->home->getSubmittedOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1145896
     */
    public function testGetSubmittedOrderViewLink()
    {
        $expectedUrl = "https://staging3.office.fedex.com/sales/order/history";

        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(1);

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
        $expectedUrl = "https://staging3.office.fedex.com/sde_default/sales/order/history/?advanced-filtering=&order-status=shipped%3Bready_for_pickup%3Bdelivered";
        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(1);

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->home->getCompletedOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1145903 - Show Order History with only shipped, ready for pickup or delivered
     */
    public function testGetCompletedOrderViewLinkWithToggleOff()
    {
        $expectedUrl = "#";
        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(0);
        $this->assertEquals($expectedUrl, $this->home->getCompletedOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1145900 - View Order  for In-Progress
     */
    public function testGetInProgressOrderViewLink()
    {
        $expectedUrl = "https://staging3.office.fedex.com/sde_default/sales/order/history/?advanced-filtering=&order-status=in_process";
        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(1);

        $this->urlInterface->expects($this->any())
            ->method('getUrl')
            ->willReturn($expectedUrl);

        $this->assertEquals($expectedUrl, $this->home->getInProgressOrderViewLink());
    }

    /**
     * @inheritDoc
     * B-1145900 - View Order  for In-Progress
     */
    public function testGetInProgressOrderViewLinkWithToggleOff()
    {
        $expectedUrl = "#";
        $this->orderHistoryDataHelper->expects($this->any())
            ->method('isSDEHomepageEnable')
            ->willReturn(0);
        $this->assertEquals($expectedUrl, $this->home->getInProgressOrderViewLink());
    }

    /**
     * Test for GetPrintProductUrl.
     *
     * B-1145888
     */
    public function testGetPrintProductUrl()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->eproHomeMock
            ->expects($this->any())
            ->method('getPrintProductUrl')
            ->willReturn('https://staging3.office.fedex.com/l6site51/print-products.html');

        $this->assertEquals('https://staging3.office.fedex.com/l6site51/print-products.html', $this->home->getPrintProductUrl());
    }
    /**
     * Test for GetPrintProductUrl.
     *
     * B-1145888
     */
    public function testGetPrintProductUrlToggleoff()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(0);
        $expectedResult = '#';
        $this->assertEquals($expectedResult, $this->home->getPrintProductUrl());
    }

    /**
     * Test for getUploadOnlyOption.
     *
     * B-1145888
     */
    public function testGetUploadOnlyOption()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue($this->companyMock));
        $this->companyMock->expects($this->any())->method('getAllowOwnDocument')->willReturn(1);
        $this->assertEquals(true, $this->home->getUploadOnlyOption());

    }
    /**
     * Test for getUploadOnlyOption.with No company Assigned
     *
     * B-1145888
     */
    public function testGetUploadOnlyOptionForNoCompany()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
        $this->companyMock->expects($this->any())->method('getAllowOwnDocument')->willReturn(1);
        $this->assertEquals(false, $this->home->getUploadOnlyOption());

    }

    /**
     * Test for getUploadOnlyOption for Not a SDE store
     *
     * B-1145888
     */
    public function testGetUploadOnlyOptionForNNoSDEStore()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(0);
        $this->companyMock = $this
            ->getMockBuilder(CompanyInterface::class)
            ->setMethods(['getAllowOwnDocument'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
        $this->companyMock->expects($this->any())->method('getAllowOwnDocument')->willReturn(1);
        $this->assertEquals(false, $this->home->getUploadOnlyOption());

    }

    /**
     * Test for getBrowseCatalogUrl.
     *
     * B-1160235
     */
    public function testGetBrowseCatalogUrl()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(1);
        $this->eproHomeMock
        ->expects($this->any())
        ->method('getBrowseCatalogUrl')
        ->willReturn('https://staging3.office.fedex.com/l6site51/browse-catalog.html');

        $this->assertEquals('https://staging3.office.fedex.com/l6site51/browse-catalog.html', $this->home->getBrowseCatalogUrl());
    }
    /**
     * Test for GetBrowseCatalogUrl.
     *
     * B-1160235
     */
    public function testGetBrowseCatalogUrlToggleoff()
    {
        $this->orderHistoryDataHelper->expects($this->any())->method('isSDEHomepageEnable')->willReturn(0);
        $expectedResult = '#';
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getBaseUrl')->willReturn($expectedResult);

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
            ->setMethods(['getAllowOwnDocument','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue($this->companyMock));
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
            ->setMethods(['getAllowOwnDocument','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
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
            ->setMethods(['getAllowOwnDocument','getAllowSharedCatalog'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->deliveryHelperMock->expects($this->any())->method('getCustomer')->will($this->returnValue($this->custonerRepositoryMock));
        $this->deliveryHelperMock->expects($this->any())->method('getAssignedCompany')->with($this->custonerRepositoryMock)->will($this->returnValue(false));
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
        $this->deliveryHelperMock->expects($this->any())->method('getProductAttributeName')->with($attributeSetId)->willReturn('PrintOnDemand');
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
        $this->assertEquals(true, $this->home->isLoaderRemovedEnable());
    }
}
