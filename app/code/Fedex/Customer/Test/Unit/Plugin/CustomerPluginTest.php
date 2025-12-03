<?php
declare(strict_types=1);
namespace Fedex\Customer\UnitTest\Test\Unit\Plguin;

use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Customer\Plugin\CustomerPlugin;
use Magento\LoginAsCustomerAssistance\Api\SetAssistanceInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerExtension;
use Magento\Company\Model\Customer;
use Magento\Framework\Api\AttributeValue;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Model\Company;
use Psr\Log\LoggerInterface;

class CustomerPluginTest extends TestCase
{
     protected $setAsstianceMock;
     protected $toggleConfigMock;
     protected $customerInterfaceMock;
     protected $customerPluginMock;
     protected $subjectMock;
     protected $customExtensionAttributeMock;
     protected $companyAttributesMock;
     protected $customAttributeMock;
     protected $companyRepositoryInterfaceMock;
     protected $companyMock;
     protected $psrinterfaceMock;


     protected function setUp(): void
     {
          $this->setAsstianceMock = $this->getMockBuilder(SetAssistanceInterface::class)
               ->setMethods(['execute'])
               ->disableOriginalConstructor()
               ->getMockForAbstractClass();
          $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
               ->disableOriginalConstructor()
               ->setMethods(['getToggleConfigValue'])
               ->getMock();
          $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
               ->setMethods(['getExtensionAttributes', 'getCustomAttribute'])
               ->getMockForAbstractClass();
          $this->subjectMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
               ->disableOriginalConstructor()
               ->getMockForAbstractClass();
          $this->customExtensionAttributeMock = $this->getMockBuilder(CustomerExtension::class)
               ->disableOriginalConstructor()
               ->setMethods(['getCompanyAttributes'])
               ->getMock();
          $this->companyAttributesMock = $this->getMockBuilder(Customer::class)
               ->disableOriginalConstructor()
               ->setMethods(['getStatus', 'getCompanyId'])
               ->getMock();
          $this->customAttributeMock = $this->getMockBuilder(AttributeValue::class)
               ->setMethods(['getValue'])
               ->getMock();
          $this->companyRepositoryInterfaceMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
               ->disableOriginalConstructor()
               ->setMethods(['get'])
               ->getMockForAbstractClass();
          $this->companyMock = $this->getMockBuilder(Company::class)
               ->disableOriginalConstructor()
               ->setMethods(['getData'])
               ->getMock();
          $this->psrinterfaceMock = $this->getMockBuilder(LoggerInterface::class)
               ->disableOriginalConstructor()
               ->setMethods(['critical'])
               ->getMockForAbstractClass();
          $objectManger = new ObjectManager($this);
          $this->customerPluginMock = $objectManger->getObject(
               CustomerPlugin::class,
               [
                    'setAssistance' => $this->setAsstianceMock,
                    'toggleConfig' => $this->toggleConfigMock,
                    'CompanyRepositoryInterface' => $this->companyRepositoryInterfaceMock,
                    'loggerInterface' => $this->psrinterfaceMock
               ]
          );
     }


     /**
      * Test afterExecute function
      */
     public function testAfterExecute()
     {
          $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);
          $this->customerInterfaceMock->expects($this->once())->method('getExtensionAttributes')->willReturn($this->customExtensionAttributeMock);
          $this->customExtensionAttributeMock->expects($this->once())->method('getCompanyAttributes')->willReturn($this->companyAttributesMock);
          $this->companyAttributesMock->expects($this->once())->method('getStatus')->willReturn(1);
          $this->customerInterfaceMock->expects($this->once())->method('getCustomAttribute')->willReturn($this->customAttributeMock);
          $this->customAttributeMock->expects($this->once())->method('getValue')->willReturn(1);
          $this->companyAttributesMock->expects($this->any())->method(constraint: 'getCompanyId')->willReturn(1);
          $this->companyRepositoryInterfaceMock->expects($this->once())->method('get')->willReturn($this->companyMock);
          $this->companyMock->expects($this->once())->method('getData')->willReturn("commercial_store_wlgn");
          $this->customerPluginMock->afterSave($this->subjectMock, $this->customerInterfaceMock, $this->customerInterfaceMock);
     }

     /**
      * Test testAfterExecuteWhenStatusIsFalse function
      */
     public function testAfterExecuteWhenStatusIsFalse()
     {
          $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);
          $this->customerInterfaceMock->expects($this->once())->method('getExtensionAttributes')->willReturn($this->customExtensionAttributeMock);
          $this->customExtensionAttributeMock->expects($this->once())->method('getCompanyAttributes')->willReturn($this->companyAttributesMock);
          $this->companyAttributesMock->expects($this->any())->method(constraint: 'getCompanyId')->willReturn(1);
          $this->companyAttributesMock->expects($this->once())->method('getStatus')->willReturn(1);
          $this->customerInterfaceMock->expects($this->once())->method('getCustomAttribute')->willReturn($this->customAttributeMock);
          $this->customAttributeMock->expects($this->once())->method('getValue')->willReturn(2);
          $this->companyRepositoryInterfaceMock->expects($this->once())->method('get')->willReturn($this->companyMock);
          $this->companyMock->expects($this->once())->method('getData')->willReturn("commercial_store_wlgn");
          $this->customerPluginMock->afterSave($this->subjectMock, $this->customerInterfaceMock, $this->customerInterfaceMock);
     }

     /**
      * Test testAfterExecuteWhenToggleIsOff function
      */
     public function testAfterExecuteWhenToggleIsOff()
     {
          $this->customerInterfaceMock->expects($this->exactly(0))->method('getExtensionAttributes')->willReturn($this->customExtensionAttributeMock);
          $this->customExtensionAttributeMock->expects($this->exactly(0))->method('getCompanyAttributes')->willReturn($this->companyAttributesMock);
          $this->customerInterfaceMock->expects($this->exactly(0))->method('getCustomAttribute')->willReturn($this->customAttributeMock);
          $this->customerPluginMock->afterSave($this->subjectMock, $this->customerInterfaceMock, $this->customerInterfaceMock);
     }

     /**
      * Test testAfterExecuteWhenLoginMethodISEpro function
      */
     public function testAfterExecuteWhenLoginMethodISEpro()
     {
          $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);
          $this->customerInterfaceMock->expects($this->once())->method('getExtensionAttributes')->willReturn($this->customExtensionAttributeMock);
          $this->customExtensionAttributeMock->expects($this->once())->method('getCompanyAttributes')->willReturn($this->companyAttributesMock);
          $this->companyAttributesMock->expects($this->once())->method('getStatus')->willReturn(1);
          $this->customerInterfaceMock->expects($this->once())->method('getCustomAttribute')->willReturn($this->customAttributeMock);
          $this->companyAttributesMock->expects($this->any())->method('getCompanyId')->willReturn(1);
          $this->companyRepositoryInterfaceMock->expects($this->once())->method('get')->willReturn($this->companyMock);
          $this->companyMock->expects($this->once())->method('getData')->willReturn("commercial_store_epro");
          $this->customerPluginMock->afterSave($this->subjectMock, $this->customerInterfaceMock, $this->customerInterfaceMock);
     }


     /**
      * Test testAfterExecuteWhenLoginMethodISEpro function
      */
     public function testAfterExecuteWhenException()
     {
          $this->toggleConfigMock->expects($this->once())->method('getToggleConfigValue')->willReturn(true);
          $this->customerInterfaceMock->expects($this->once())->method('getExtensionAttributes')->willReturn($this->customExtensionAttributeMock);
          $this->customExtensionAttributeMock->expects($this->once())->method('getCompanyAttributes')->willReturn($this->companyAttributesMock);
          $this->customerInterfaceMock->expects($this->once())->method('getCustomAttribute')->willReturn($this->customAttributeMock);
          $this->companyAttributesMock->expects($this->any())->method('getCompanyId')->willReturn(1);
          $this->companyRepositoryInterfaceMock->expects($this->once())->method('get')->
               willReturn($this->returnCallback(function () {
                    throw new \Exception('invalid argument exception');
               }));
          $this->customerPluginMock->afterSave($this->subjectMock, $this->customerInterfaceMock, $this->customerInterfaceMock);
     }


}
