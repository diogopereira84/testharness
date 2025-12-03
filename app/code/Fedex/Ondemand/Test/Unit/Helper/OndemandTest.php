<?php

namespace Fedex\Ondemand\Test\Unit\Helper;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Company\Model\CompanyFactory;
use Magento\Company\Model\Company;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreFactory;
use Fedex\Ondemand\Helper\Ondemand;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Catalog\Model\Category;
use Magento\Framework\App\Request\Http;
use Magento\Catalog\Model\CategoryFactory;
use Fedex\CatalogMvp\Helper\CatalogMvp;

class OndemandTest extends TestCase
{
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    protected $sessionFactory;
    protected $session;
    protected $catalogMvpHelper;
    protected $category;
    protected $categoryFactory;
    protected $scopeConfigInterface;
    protected $deliveryHelper;
    protected $companyRepository;
    protected $toggleConfigMock;
    /**
     * @var (\Magento\Framework\UrlInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $urlMock;
    protected $companyFactoryMock;
    protected $companyMock;
    protected $companyCollection;
    protected $selfRegMock;
    protected $storeFactoryMock;
    protected $storeMock;
    protected $httpMock;
    protected $ondemand;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

		$this->sessionFactory = $this->getMockBuilder(SessionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

		$this->session = $this->getMockBuilder(Session::class)
			->disableOriginalConstructor()
			->setMethods(['isLoggedIn','getId','setCustomerCompany'])
			->getMock();

		$this->catalogMvpHelper = $this->getMockBuilder(CatalogMvp::class)
			->disableOriginalConstructor()
			->setMethods(['checkPrintCategory'])
			->getMock();

		$this->category = $this->getMockBuilder(Category::class)
			->disableOriginalConstructor()
			->setMethods(['getName','getId','load','getProductCollection','addAttributeToSelect','count','getPath'])
			->getMock();

		$this->categoryFactory = $this->getMockBuilder(CategoryFactory::class)
        	->disableOriginalConstructor()
        	->setMethods(['create'])
        	->getMock();

		$this->scopeConfigInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

		$this->deliveryHelper = $this->getMockBuilder(DeliveryHelper::class)
            ->disableOriginalConstructor()
			->setMethods(['isCommercialCustomer'])
            ->getMock();

		$this->companyRepository = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
		
		$this->urlMock = $this->getMockBuilder(UrlInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCurrentUrl'])
            ->getMockForAbstractClass();
            
		$this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
          
		$this->companyMock = $this->getMockBuilder(Company::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection','getId','load','getData'])
            ->getMock();
        
        $this->companyCollection = $this->getMockBuilder(\Magento\Company\Model\ResourceModel\Company\Collection::class)
            ->disableOriginalConstructor()
            ->setMethods(['addFieldToFilter', 'getFirstItem', 'getData'])
            ->getMock();
                
		$this->selfRegMock = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['checkSelfRegEnable'])
            ->getMock();
            
		$this->storeFactoryMock = $this->getMockBuilder(StoreFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
            
		$this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->setMethods(['load', 'getUrl'])
            ->getMock();
            
		$this->httpMock = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMock();
            
        $objectManagerHelper = new ObjectManager($this);
        $this->ondemand = $objectManagerHelper->getObject(
            Ondemand::class,
            [
                'context' => $this->contextMock,
                'toggleConfig' => $this->toggleConfigMock,
                'urlInterface' => $this->urlMock,
                'companyFactory' => $this->companyFactoryMock,
                'selfReg' => $this->selfRegMock,
                'storeFactory' => $this->storeFactoryMock,
				'sessionFactory' => $this->sessionFactory,
        		'scopeConfigInterface' => $this->scopeConfigInterface,
				'deliveryHelper' => $this->deliveryHelper,
				'companyRepository' => $this->companyRepository,
 				'requestHttp' => $this->httpMock,
 				'categoryFactory' => $this->categoryFactory,
				'catalogMvpHelper' => $this->catalogMvpHelper
            ]
        );
    }
    
    

    /**
     * testGetOndemandCompanyDataForEpro
     */
    public function testGetOndemandCompanyDataForEpro()
    {
		$companyData = [
						'entity_id' => 3, 
						'company_id' => 5, 
						'company_data' => ['entity_id' => 3], 
						'ondemand_url' => true,
						'is_sensitive_data_enabled' => false,
						'url_extension' => true,
						'company_type' => 'epro'
					];
		$currentUrl = 'https://staging3.office.fedex.com/ondemand?company=me';
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->httpMock->expects($this->any())->method('getParam')->willReturn('me');
		
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
		
		$this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getData')->willReturn($companyData);
		
		$this->assertEquals('epro', $this->ondemand->getOndemandCompanyData()['company_type']);
    }
    
    /**
     * testGetOndemandCompanyDataForSelfReg
     */
    public function testGetOndemandCompanyDataForSelfReg()
    {
		$companyData = [
						'entity_id' => 3, 
						'company_id' => 5, 
						'company_data' => ['entity_id' => 3], 
						'ondemand_url' => true,
						'is_sensitive_data_enabled' => false,
						'url_extension' => true,
						'company_type' => 'selfreg'
					];
		$currentUrl = 'https://staging3.office.fedex.com/ondemand?company=me';
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->httpMock->expects($this->any())->method('getParam')->willReturn('me');
		
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
		
		$this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getData')->willReturn($companyData);
		
		$this->selfRegMock->expects($this->any())->method('checkSelfRegEnable')->willReturn(true);
		
		$this->assertEquals('selfreg', $this->ondemand->getOndemandCompanyData()['company_type']);
 		
    }
    
    /**
     * testGetOndemandCompanyDataForSDE
     */
    public function testGetOndemandCompanyDataForSDE()
    {
		$companyData = [
						'entity_id' => 3, 
						'company_id' => 5, 
						'company_data' => ['entity_id' => 3], 
						'ondemand_url' => true,
						'is_sensitive_data_enabled' => true,
						'url_extension' => true,
						'company_type' => 'sde'
					];
		$currentUrl = 'https://staging3.office.fedex.com/ondemand?company=sde_default';
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->httpMock->expects($this->any())->method('getParam')->willReturn('sde_default');
				
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
		$this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getData')->willReturn($companyData);
		
		$this->selfRegMock->expects($this->any())->method('checkSelfRegEnable')->willReturn(true);
		
		$this->assertEquals('sde', $this->ondemand->getOndemandCompanyData()['company_type']);
    }
    
    /**
     * testCompanyDataForOndemandUrl
     */
    public function testCompanyDataForOndemandUrl()
    {
		$companyData = [
						'entity_id' => 3, 
						'company_id' => 5, 
						'company_data' => ['entity_id' => 3], 
						'ondemand_url' => true,
						'is_sensitive_data_enabled' => true,
						'url_extension' => true,
						'company_type' => 'sde'
					];
		$currentUrl = 'https://staging3.office.fedex.com/ondemand/';
		
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->httpMock->expects($this->any())->method('getParam')->willReturn(false);
		
		$this->assertIsArray($this->ondemand->getOndemandCompanyData());
 		
    }
    
    /**
     * testCompanyDataIfEntityNotFound
     */
    public function testCompanyDataIfEntityNotFound()
    {
		$companyData = [
						'company_id' => 5, 
						'company_data' => ['entity_id' => 3], 
						'ondemand_url' => true,
						'is_sensitive_data_enabled' => true,
						'url_extension' => true,
						'company_type' => 'sde'
					];
		$currentUrl = 'https://staging3.office.fedex.com/ondemand?company=me';
		
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->httpMock->expects($this->any())->method('getParam')->willReturn('me');
		
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getCollection')->willReturn($this->companyCollection);
		$this->companyCollection->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getFirstItem')->willReturnSelf();
		$this->companyCollection->expects($this->any())->method('getData')->willReturn(false);
		
		$this->assertIsArray($this->ondemand->getOndemandCompanyData());
    }
    
    /**
     * testGetOndemandStoreUrl
     */
    public function testGetOndemandStoreUrl()
    {
		$currentUrl = 'https://staging3.office.fedex.com/ondemand/me/';
		$this->storeFactoryMock->expects($this->any())->method('create')->willReturn($this->storeMock);
		$this->storeMock->expects($this->any())->method('load')->willReturnSelf();
		$this->storeMock->expects($this->any())->method('getUrl')->willReturn($currentUrl);
		
		$this->assertIsString($this->ondemand->getOndemandStoreUrl());
    }

	public function testGetCustomerTypeFromSession()
    {
		$this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);
		$this->session->expects($this->any())->method('isLoggedIn')->willReturn(true);
		$this->session->expects($this->any())->method('getId')->willReturn(2);
		$this->session->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
		$this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getId')->willReturn(2);
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('load')->willReturnSelf();
		$this->companyMock->expects($this->any())->method('getData')->with('is_sensitive_data_enabled')->willReturn(false);
		$this->selfRegMock->expects($this->any())->method('checkSelfRegEnable')->willReturn(true);
		$this->assertIsString($this->ondemand->getCustomerTypeFromSession());
    }

	public function testGetCustomerTypeFromSessionSDE()
    {
		$this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);
		$this->session->expects($this->any())->method('isLoggedIn')->willReturn(true);
		$this->session->expects($this->any())->method('getId')->willReturn(2);
		$this->session->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
		$this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getId')->willReturn(2);
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('load')->willReturnSelf();
		$this->companyMock->expects($this->any())->method('getData')->with('is_sensitive_data_enabled')->willReturn(true);
		$this->selfRegMock->expects($this->any())->method('checkSelfRegEnable')->willReturn(false);
		$this->assertIsString($this->ondemand->getCustomerTypeFromSession());
    }

	public function testGetCustomerTypeFromSessionEpro()
    {
		$this->getCustomerTypeFromSessionEpro();
		$this->assertIsString($this->ondemand->getCustomerTypeFromSession());
    }

	public function getCustomerTypeFromSessionEpro()
	{
		$this->sessionFactory->expects($this->any())->method('create')->willReturn($this->session);
		$this->session->expects($this->any())->method('isLoggedIn')->willReturn(true);
		$this->session->expects($this->any())->method('getId')->willReturn(2);
		$this->session->expects($this->any())->method('setCustomerCompany')->willReturnSelf();
		$this->companyRepository->expects($this->any())->method('getByCustomerId')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('getId')->willReturn(2);
		$this->companyFactoryMock->expects($this->any())->method('create')->willReturn($this->companyMock);
		$this->companyMock->expects($this->any())->method('load')->willReturnSelf();
		$this->companyMock->expects($this->any())->method('getData')->with('is_sensitive_data_enabled')->willReturn(false);
		$this->selfRegMock->expects($this->any())->method('checkSelfRegEnable')->willReturn(false);
		$this->selfRegMock->expects($this->any())->method('checkSelfRegEnable')->willReturn(false);
		$this->deliveryHelper->expects($this->any())->method('isCommercialCustomer')->willReturn(true);
	}
	public function testGetPrintProductCategory()
	{
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->category->expects($this->any())->method('getName')->willReturn("Print Products");
		$this->category->expects($this->any())->method('getId')->willReturn("category-node-441");
		$this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn("441");
		$this->getCustomerTypeFromSessionEpro();
		$this->assertEquals(true, $this->ondemand->getPrintProductCategory($this->category, 0));
	}

	public function testIsProductAvailable()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->catalogMvpHelper->expects($this->any())->method('checkPrintCategory')->willReturn(true);
		$this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn("21");
        $this->category->expects($this->any())->method('getId')->willReturn("category-node-441");
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);
		$this->category->expects($this->any())->method('getPath')->willReturn("2/21/45");
        $this->category->expects($this->any())->method('getProductCollection')->willReturn($this->category);
        $this->category->expects($this->any())->method('addAttributeToSelect')->willReturn($this->category);
        $this->category->expects($this->any())->method('count')->willReturn(1);
        $this->assertEquals(true, $this->ondemand->isProductAvailable($this->category, 1));
    }

	public function testIsProductAvailableCountZero()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->catalogMvpHelper->expects($this->any())->method('checkPrintCategory')->willReturn(true);
		$this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn("22");
        $this->category->expects($this->any())->method('getId')->willReturn("category-node-441");
        $this->categoryFactory->expects($this->any())->method('create')->willReturn($this->category);
        $this->category->expects($this->any())->method('load')->willReturn($this->category);
		$this->category->expects($this->any())->method('getPath')->willReturn("2/21/22");
        $this->category->expects($this->any())->method('getProductCollection')->willReturn($this->category);
        $this->category->expects($this->any())->method('addAttributeToSelect')->willReturn($this->category);
        $this->category->expects($this->any())->method('count')->willReturn(0);
        $this->assertEquals(false, $this->ondemand->isProductAvailable($this->category, 1));
    }

	public function testGetPrintProductCategoryBrowse()
	{
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->category->expects($this->any())->method('getName')->willReturn("browse catalog");
		$this->category->expects($this->any())->method('getId')->willReturn("category-node-441");
		$this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn("441");
		$this->getCustomerTypeFromSessionEpro();
		$this->assertEquals(true, $this->ondemand->getPrintProductCategory($this->category, 0));
	}
	public function testGetPrintProductCategoryNon()
	{
		$this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
		$this->category->expects($this->any())->method('getName')->willReturn("Print Products");
		$this->category->expects($this->any())->method('getId')->willReturn("category-node-441");
		$this->scopeConfigInterface->expects($this->any())->method('getValue')->willReturn("21");
		$this->getCustomerTypeFromSessionEpro();
		$this->assertEquals(false, $this->ondemand->getPrintProductCategory($this->category, 0));
	}
}
