<?php

namespace Fedex\CatalogMvp\Test\Unit\Plugin;

use Magento\Store\Model\StoreManagerInterface;
use Fedex\CustomizedMegamenu\Block\Html\Topmenu as ParentTopmenu;
use Fedex\CatalogMvp\Helper\CatalogMvp;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\SDE\Helper\SdeHelper;
use Magento\Framework\UrlInterface;
use Fedex\CatalogMvp\Plugin\Topmenu;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Customer\Model\Session;
use Magento\Company\Api\Data\CompanyInterface;

class TopmenuTest extends TestCase
{
    protected $storeManagerInterfaceMock;
    protected $catalogMvpHelperMock;
    protected $deliveryHelperMock;
    protected $sdeHelperMock;
    protected $urlInterfaceMock;
    protected $parentTopmenuMock;
    /**
     * @var (\Magento\Catalog\Model\CategoryFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $categoryFactoryMock;
    /**
     * @var (\Magento\Customer\Model\Session & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $customerSessionMock;
    /**
     * @var (\Magento\Company\Api\Data\CompanyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $companyInterface;
    protected $topmenu;
    protected function setUp(): void
    {
        $this->storeManagerInterfaceMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getBaseUrl'])
            ->getMockForAbstractClass();
            
        $this->catalogMvpHelperMock = $this->getMockBuilder(CatalogMvp::class)
            ->disableOriginalConstructor()
            ->setMethods(['isMvpCustomerAdminEnable','isEproHomepageEnabled','getCompanySharedCatName'])
            ->getMock();
            
        $this->deliveryHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['isCommercialCustomer','getAssignedCompany'])
            ->getMock();
            
        $this->sdeHelperMock = $this->getMockBuilder(SdeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIsSdeStore'])
            ->getMock();
            
        $this->urlInterfaceMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMockForAbstractClass();
        
        $this->parentTopmenuMock = $this->getMockBuilder(ParentTopmenu::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryFactoryMock = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load','getName'])
            ->getMock();
        
        $this->customerSessionMock = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOndemandCompanyInfo'])
            ->getMock();
        
        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSharedCatalogId'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->topmenu = $objectManagerHelper->getObject(
            Topmenu::class,
            [
                'storeManagerInterface' => $this->storeManagerInterfaceMock,
                'catalogMvpHelper' => $this->catalogMvpHelperMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'sdeHelper' => $this->sdeHelperMock,
                'urlInterface' => $this->urlInterfaceMock,
                'categoryFactory' => $this->categoryFactoryMock
            ]
        );
    }

    /**
     * @test testAfterGetMegaMenuHtml
     */
    public function testAfterGetMegaMenuHtml()
    {
        $baseUrl = 'https://staging3.office.fedex.com/me/';
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpCustomerAdminEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isEproHomepageEnabled')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->urlInterfaceMock->expects($this->any())->method('getCurrentUrl')->willReturn($baseUrl);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCompanySharedCatName')->willReturn('test');
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->assertIsString($this->topmenu->afterGetMegaMenuHtml($this->parentTopmenuMock, '<ul></ul>'));
    }

    /**
     * @test testAfterGetMegaMenuHtmlforNullSharedCatId
     */
    public function testAfterGetMegaMenuHtmlforNullSharedCatId()
    {
        $baseUrl = 'https://staging3.office.fedex.com/me/';
        $this->catalogMvpHelperMock->expects($this->any())->method('isMvpCustomerAdminEnable')->willReturn(true);
        $this->catalogMvpHelperMock->expects($this->any())->method('isEproHomepageEnabled')->willReturn(true);
        $this->deliveryHelperMock->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
        $this->sdeHelperMock->expects($this->any())->method('getIsSdeStore')->willReturn(false);
        $this->urlInterfaceMock->expects($this->any())->method('getCurrentUrl')->willReturn($baseUrl);
        $this->catalogMvpHelperMock->expects($this->any())->method('getCompanySharedCatName')->willReturn('test');
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManagerInterfaceMock->expects($this->any())->method('getBaseUrl')->willReturn($baseUrl);
        $this->assertIsString($this->topmenu->afterGetMegaMenuHtml($this->parentTopmenuMock, '<ul></ul>'));
    }
}
