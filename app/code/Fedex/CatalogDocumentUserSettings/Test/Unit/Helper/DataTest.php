<?php

namespace Fedex\CatalogDocumentUserSettings\Test\Unit\Helper;

use Fedex\CatalogDocumentUserSettings\Helper\Data;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Catalog\Model\Category as CategoryModel;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Customer\Model\Customer;
use Fedex\Base\Helper\Auth;

class DataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $customerSession;
    protected $customerSessionMock;
    protected $companyMock;
    protected $companyFactoryMock;
    protected $userMock;
    protected $userFactoryMock;
    protected $registryMock;
    protected $requestMock;
    protected $toggleConfigMock;
    protected $storeManagerInterfaceMock;
    /**
     * @var (\Magento\Store\Api\Data\StoreInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeInterfaceMock;
    protected $storeMock;
    protected $categoryModel;
    protected $categoryFactory;
    protected $customer;
    protected $customerSessionMain;
    protected $baseAuthMock;
    protected $data;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(\Magento\Framework\App\Helper\Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSessionMock = $this->getMockBuilder(\Magento\Customer\Model\SessionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','getCustomerCompany'])
            ->getMock();

        $this->companyMock = $this->getMockBuilder(\Magento\Company\Model\Company::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyFactoryMock = $this->getMockBuilder(\Magento\Company\Model\CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load'])
            ->getMock();

        $this->userMock = $this->getMockBuilder(\Magento\User\Model\User::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->userFactoryMock = $this->getMockBuilder(\Magento\User\Model\UserFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load','getFirstName'])
            ->getMock();

        $this->registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->setMethods(['registry'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\Request\Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getFullActionName'])
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(\Fedex\EnvironmentManager\ViewModel\ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

        $this->storeManagerInterfaceMock = $this->getMockBuilder(\Magento\Store\Model\StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore'])
            ->getMockForAbstractClass();

        $this->storeInterfaceMock = $this->getMockBuilder(\Magento\Store\Api\Data\StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStoreId','getRootCategoryId'])
            ->getMock();

        $this->categoryModel = $this->getMockBuilder(CategoryModel::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getName', 'getCategories', 'getId', 'hasChildren', 'getChildren'])
            ->getMock();

        $this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getName', 'getCategories'])
            ->getMock();

        $this->customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->setMethods(['getGroupId'])
            ->getMock();

        $this->customerSessionMain = $this->getMockBuilder(\Magento\Customer\Model\Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['isLoggedIn', 'getCustomer'])
            ->getMock();

        $this->baseAuthMock = $this->getMockBuilder(Auth::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isLoggedIn'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);
        $this->data = $objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->contextMock,
                'customerSessionFactory' => $this->customerSessionMock,
                'companyFactory' => $this->companyFactoryMock,
                'registry' => $this->registryMock,
                'request' => $this->requestMock,
                'storeManager' => $this->storeManagerInterfaceMock,
                'userFactory' => $this->userFactoryMock,
                'categoryFactory' => $this->categoryFactory,
                'customerSession' => $this->customerSessionMain
            ]
        );
    }

    /**
     * @test getCompanyConfiguration
     *
     */
    public function testGetCompanyConfiguration()
    {
        $compayId = 1;
        $this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
        $this->customerSessionMock->expects($this->any())->method('create')->willReturn($this->customerSession);
        $this->customerSessionMock->expects($this->any())->method('getCustomerCompany')->willReturn($compayId);
        $this->companyFactoryMock->expects($this->any())->method('load')->willReturn($this->companyMock);

        $this->assertEquals(null, $this->data->getCompanyConfiguration());
    }

    /**
     * @test getCurrentCategory
     *
     */
    public function testGetCurrentCategory()
    {
        $category = null;
        $this->registryMock->expects($this->any())->method('registry')->with('current_category')->willReturn($category);

        $this->assertEquals($category, $this->data->getCurrentCategory());
    }

    /**
     * @test getActionName
     *
     */
    public function testGetActionName()
    {
        $currentPageactionName = null;
        $this->requestMock->expects($this->any())->method('getFullActionName')->willReturn($currentPageactionName);

        $this->assertEquals($currentPageactionName, $this->data->getActionName());
    }

    /**
     * @test getFirstName
     *
     */
    public function testGetFirstName()
    {
        $this->userFactoryMock->expects($this->any())->method('create')->willReturn($this->userMock);
        $this->userMock->expects($this->any())->method('load')->willReturn($this->userMock);
        $this->userMock->expects($this->any())->method('getFirstname')->willReturn('string');

        $this->assertEquals('string', $this->data->getFirstName(1));
    }


    /**
     * @test testGetBrowseCatalogLink
     *
     */
    public function testGetBrowseCatalogLink()
    {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(1);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('load')->with(1)->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getName')->willReturn('Browse Catalog');
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMain->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(12);

        $this->categoryModel->expects($this->any())->method('getCategories')->willReturn([$this->categoryModel]);

        $this->assertEquals(null, $this->data->getBrowseCatalogLink());
    }

    /**
     * @test testGetBrowseCatalogLink
     *
     */
    public function testGetBrowseCatalogLink1()
    {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(1);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('load')->with(1)->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getName')->willReturn('Browse Catalog');
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMain->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(12);

        $this->categoryModel->expects($this->any())->method('getCategories')->willReturn([]);

        $this->assertEquals(null, $this->data->getBrowseCatalogLink());
    }

    /**
     * @test testGetPrintProductLink
     *
     */
    public function testGetPrintProductLink()
    {
        $this->storeManagerInterfaceMock->expects($this->any())->method('getStore')->willReturn($this->storeMock);
        $this->storeMock->expects($this->any())->method('getStoreId')->willReturn(1);
        $this->storeMock->expects($this->any())->method('getRootCategoryId')->willReturn(1);

        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('load')->with(1)->willReturn($this->categoryModel);
        $this->categoryModel->expects($this->any())->method('getName')->willReturn('Print Products');
        $this->baseAuthMock->expects($this->any())->method('isLoggedIn')->willReturn(true);
        $this->customerSessionMain->expects($this->any())->method('getCustomer')->willReturn($this->customer);
        $this->customer->expects($this->any())->method('getGroupId')->willReturn(12);

        $this->categoryModel->expects($this->any())->method('getCategories')->willReturn([$this->categoryModel]);

        $this->assertEquals(null, $this->data->getPrintProductLink());
    }

    /**
     * testGetToggleStatusForPerformanceImprovmentPhasetwo
     * @return void
     */
    public function testGetToggleStatusForPerformanceImprovmentPhasetwo()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(false);

        $this->assertEquals(false, $this->data->getToggleStatusForPerformanceImprovmentPhasetwo());
    }

}
