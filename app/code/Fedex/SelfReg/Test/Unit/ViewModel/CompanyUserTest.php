<?php
namespace Fedex\SelfReg\Test\Unit\ViewModel;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\SelfReg\ViewModel\CompanyUser;
use Fedex\Delivery\Helper\Data;
use Fedex\CatalogDocumentUserSettings\Helper\Data as CatalogSettingsHelper;
use Magento\Company\Model\Company;
use Magento\Customer\Model\SessionFactory;
use Magento\Customer\Model\Session;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanyExtensionInterface;

class CompanyUserTest extends TestCase
{
    protected $toggleConfigMock;
    protected $companyHelperMock;
    protected $catalogSettingHelperMock;
    protected $companyMock;
    protected $customerSessionFactoryMock;
    protected $sessionMock;
    protected $companyRepositoryMock;
    protected $companyInterface;
    protected $companyExtensionInterfaceMock;
    protected $data;
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->companyHelperMock = $this->getMockBuilder(Data::class)
             ->disableOriginalConstructor()
             ->setMethods(['isEproCustomer', 'isCustomerAdminUser'])
             ->getMock();
        
        $this->catalogSettingHelperMock = $this->getMockBuilder(CatalogSettingsHelper::class)
             ->disableOriginalConstructor()
             ->setMethods(['getCompanyConfiguration'])
             ->getMock();
        
        $this->companyMock = $this->getMockBuilder(Company::class)
             ->disableOriginalConstructor()
             ->setMethods(['getAllowSharedCatalog'])
             ->getMock();

        $this->customerSessionFactoryMock = $this->getMockBuilder(SessionFactory::class)
             ->disableOriginalConstructor()
             ->setMethods(['create'])
             ->getMockForAbstractClass();
  
        $this->sessionMock = $this->getMockBuilder(Session::class)
             ->disableOriginalConstructor()
             ->setMethods(['getId'])
             ->getMockForAbstractClass();
  
        $this->companyRepositoryMock = $this->getMockBuilder(CompanyManagementInterface::class)
             ->disableOriginalConstructor()
             ->getMockForAbstractClass();
  
        $this->companyInterface = $this->getMockBuilder(CompanyInterface::class)
             ->disableOriginalConstructor()
             ->setMethods(['getByCustomerId','getExtensionAttributes','getCompanyAdditionalData'])
             ->getMockForAbstractClass();
  
        $this->companyExtensionInterfaceMock = $this->getMockBuilder(CompanyExtensionInterface::class)
             ->disableOriginalConstructor()
             ->setMethods([
                 'getCompanyAdditionalData',
                 'getIsApprovalWorkflowEnabled'
             ])
             ->getMockForAbstractClass();   
            $objectManagerHelper = new ObjectManager($this);
            $this->data = $objectManagerHelper->getObject(
                CompanyUser::class,
                [
                    'toggleConfig' => $this->toggleConfigMock,
                    'companyHelper' => $this->companyHelperMock,
                    'helper' => $this->catalogSettingHelperMock,
                    'customerSessionFactory' => $this->customerSessionFactoryMock,
                    'companyRepository' => $this->companyRepositoryMock
                ]
            );
    }

    /**
     * @test testToggleCustomerRolesAndPermissions
     */
    public function testToggleCustomerRolesAndPermissions()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->toggleCustomerRolesAndPermissions());
    }

    public function testIsEproCustomer()
    {
        $this->companyHelperMock->expects($this->any())
             ->method('isEproCustomer')
             ->willReturn(true);
        $this->assertEquals(true,$this->data->isEproCustomer());
    }

    /**
     * @test testgetUserGroupOrderApproversToggle
     */
    public function testgetUserGroupOrderApproversToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertEquals(true, $this->data->getUserGroupOrderApproversToggle());
    }

    /**
     * @test testGetCompanySettingsToggle
     */
    public function testGetCompanySettingsToggle()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->data->getCompanySettingsToggle());
    }

    /**
     * @test testisLoaderRemovedEnable
     */
    public function testisLoaderRemovedEnable()
    {
        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);
        $this->assertNotNull($this->data->isLoaderRemovedEnable());
    }

    /**
     * @test testisAllowedSharedCatalog when company is returen
     */
    public function testIsAllowedSharedCatalog() 
    {
        $this->catalogSettingHelperMock->expects($this->any())
        ->method('getCompanyConfiguration')
        ->willReturn($this->companyMock);

        $this->companyMock->expects($this->any())
        ->method('getAllowSharedCatalog')
        ->willReturn(true);

        $this->assertNotNull($this->data->isAllowedSharedCatalog());
    }

    /**
     * @test testisAllowedSharedCatalog when company is null
     */
    public function testIsAllowedSharedCatalogFalse() 
    {
        $this->catalogSettingHelperMock->expects($this->any())
        ->method('getCompanyConfiguration')
        ->willReturn(null);
        $this->assertNotNull($this->data->isAllowedSharedCatalog());
    }

    /**
     * @test testisB2BOrderAprovalEnable
     */
    public function testisB2BOrderAprovalEnable()
    {
        $this->customerSessionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->sessionMock);
        $this->sessionMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);
        $this->companyRepositoryMock->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn($this->companyInterface);

       $this->companyInterface->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->companyExtensionInterfaceMock);

      $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getCompanyAdditionalData')
            ->willReturnSelf();

     $this->companyExtensionInterfaceMock->expects($this->any())
            ->method('getIsApprovalWorkflowEnabled')
            ->willReturn(true);

        $this->assertTrue($this->data->isB2BOrderAprovalEnable());
    }

    /**
     * @test testisB2BOrderAprovalEnable false
     */
    public function testisB2BOrderAprovalEnableFalse()
    {
        $this->customerSessionFactoryMock
            ->expects($this->any())
            ->method('create')
            ->willReturn($this->sessionMock);
        $this->sessionMock
            ->expects($this->any())
            ->method('getId')
            ->willReturn(12);
        $this->companyRepositoryMock->expects($this->any())
            ->method('getByCustomerId')
            ->willReturn(null);
        $this->assertFalse($this->data->isB2BOrderAprovalEnable());
    }

    
    /**
     * @test displayGroupTypeSection
     */
    public function testDisplayGroupTypeSection () {
        
        $this->testIsAllowedSharedCatalog();
        $this->testisB2BOrderAprovalEnable();
        $this->testgetUserGroupOrderApproversToggle();
        $this->assertTrue($this->data->displayGroupTypeSection());
    }

    /**
     * @test displayGroupTypeSection false
     */
    public function testDisplayGroupTypeSectionFalse () {
        $this->testIsAllowedSharedCatalog();
        $this->testisB2BOrderAprovalEnableFalse();
        $this->testgetUserGroupOrderApproversToggle();
        $this->assertFalse($this->data->displayGroupTypeSection());
    }
}
