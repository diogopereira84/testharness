<?php
/**
 * Fedex_CatalogMvp
 *
 * @category   Fedex
 * @package    Fedex_CatalogMvp
 * @author     Manish Chaubey
 * @email      manish.chaubey.osv@fedex.com
 * @copyright  © FedEx, Inc. All rights reserved.
 */

namespace Fedex\CatalogMvp\Test\Unit\Helper;

use Fedex\CatalogMvp\Helper\EmailHelper;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\CIDPSG\Helper\Email;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Helper\Context;
use PHPUnit\Framework\TestCase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Company\Model\CompanyFactory;
use Magento\Backend\Helper\Data;
use Magento\Customer\Model\CustomerFactory;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class EmailHelperTest extends TestCase
{
     /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $storeManager;
    protected $companyFactoryMock;
    protected $customerFactoryMock;
    protected $backendHelperMock;
    protected $emailHelper;
    /**
      * @var RequestInterface|MockObject
      */
    protected $requestMock;

    /**
     * @var Email|MockObject
     */
    protected $emailMock;

    /**
     * @var StoreManagerInterface|MockObject
     */
    protected $storeManagerMock;

    /**
     * @var SelfReg|MockObject
     */
    protected $selfRegHelper;
    protected $customerRepositoryInterfaceMock;
    protected $toggleConfigMock;
    

    /**
     * Init mocks for tests.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock=$this->getMockBuilder(LoggerInterface::class)
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();

        $this->emailMock = $this->getMockBuilder(Email::class)
            ->disableOriginalConstructor()
            ->setMethods(['loadEmailTemplate', 'callGenericEmailApi','sendEmail'])
            ->getMockForAbstractClass();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->setMethods(['getStore', 'getId','getBaseUrl'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->companyFactoryMock = $this->getMockBuilder(CompanyFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','addFieldToFilter','getCollection', 'getFirstItem',
                'getSuperUserId', 'getNonStandardCatalogDistributionList', 'getCompanyName'])
            ->getMockForAbstractClass();

        $this->customerFactoryMock = $this->getMockBuilder(customerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'load', 'getSecondaryEmail', 'getName', 'getCustomAttribute', 'getFirstName', 'getLastName'])
            ->getMock();

        $this->backendHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->setMethods(['getUrl'])
            ->getMockForAbstractClass();

        $this->selfRegHelper = $this->getMockBuilder(SelfReg::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEmailNotificationAllowUserList'])
            ->getMockForAbstractClass();
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->customerRepositoryInterfaceMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $objectManagerHelper = new ObjectManager($this);
        $this->emailHelper = $objectManagerHelper->getObject(
            EmailHelper::class,
            [
                'context' => $this->contextMock,
                'logger'=>$this->loggerMock,
                'email' => $this->emailMock,
                'storeManager' => $this->storeManager,
                'companyFactory' => $this->companyFactoryMock,
                'backendHelper' => $this->backendHelperMock,
                'customerFactory' => $this->customerFactoryMock,
                'selfRegHelper' => $this->selfRegHelper,
                'customerRepositoryInterface'=>$this->customerRepositoryInterfaceMock,
                'toggleConfig'=>$this->toggleConfigMock
            ]
        );
        $this->emailHelper->status = 'confirmed';
    }

     /**
      * Test method for sendQuoteGenericEmail function for pricable false
      *
      * @return void
      */
    public function testSendReadyForReviewEmail()
    {
        $productData['product_name'] = 'test product';
        $productData['admin_name'] = 'Test';
        $productData['site_name'] = 'Fedex Ondemand';
        $productData['item_name'] = 'Test Prodcut';
        $productData['folder_path'] = 'b2brootcategory/testcategory/';
        $productData['added_by'] = 'Customer Admin';
        $productData['special_instruction'] = 'This is special instruction';
        $productData['product_id'] = 1;
        $productData['company_id'] = 98;
        $productData['customer_email'] = 'test@gmail.com';
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(45);
        $this->companyFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getSuperUserId')->willReturn(28);
        $this->companyFactoryMock->expects($this->any())
            ->method('getNonStandardCatalogDistributionList')->willReturn('test@infogain.com');
            $this->companyFactoryMock->expects($this->any())->method('getCompanyName')->willReturn('infogain');
        $this->emailMock->expects($this->once())->method('sendEmail')->willReturnSelf();
        $this->backendHelperMock->expects($this->any())->method('getUrl')
            ->willReturn('https://test.office.fedex.com/stage3fedex7id4w/test/catalog/product/view/id/1234');
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('load')
            ->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('getSecondaryEmail')
            ->willReturn('abc@test.com');
        $this->customerFactoryMock->expects($this->any())->method('getName')
            ->willReturn('test');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);  
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerFactoryMock);     
        $result = $this->emailHelper->sendReadyForReviewEmail($productData);
        $this->assertNotNull($result);
    }

    /**
     * Test method for sendQuoteGenericEmail function for pricable false
     *
     * @return void
     */
    public function testSendReadyForReviewEmailCustomerAdmin()
    {
        $productData['product_name'] = 'test product';
        $productData['admin_name'] = 'Test';
        $productData['site_name'] = 'Fedex Ondemand';
        $productData['item_name'] = 'Test Prodcut';
        $productData['folder_path'] = 'b2brootcategory/testcategory/';
        $productData['special_instruction'] = 'This is special instruction';
        $productData['product_id'] = 1;
        $productData['company_id'] = 98;
        $productData['customer_email'] = 'abctest@gmail.com';
        $productData['customer_name'] = 'Test';

        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(45);
        $this->companyFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getSuperUserId')->willReturn(28);
        $this->companyFactoryMock->expects($this->any())
            ->method('getNonStandardCatalogDistributionList')->willReturn('test@infogain.com');
        $this->companyFactoryMock->expects($this->any())->method('getCompanyName')->willReturn('infogain');
        $this->emailMock->expects($this->once())->method('sendEmail')->willReturnSelf();
        $this->backendHelperMock->expects($this->any())->method('getUrl')
            ->willReturn('https://test.office.fedex.com/stage3fedex7id4w/catalog/product/');
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('load')
            ->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('getSecondaryEmail')
            ->willReturn('abc@test.com');
        $this->customerFactoryMock->expects($this->any())->method('getName')
            ->willReturn('test');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);    
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerFactoryMock);   
        $result = $this->emailHelper->sendReadyForReviewEmailCustomerAdmin($productData);
        $this->assertNotNull($result);
    }

    /**
     * Test method for sendQuoteGenericEmail function for pricable false
     *
     * @return void
     */
    public function testSendReadyForOrderEmailCustomerAdmin()
    {
        $productData['product_name'] = 'test product';
        $productData['admin_name'] = 'Test';
        $productData['folder_path'] = 'b2brootcategory/testcategory/';
        $productData['company_id'] = 98;
        $productData['customer_email'] = 'abctest@gmail.com';
        $productData['customer_name'] = 'Test';

        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(45);
        $this->companyFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getFirstItem')->willReturnSelf();
        $this->companyFactoryMock->expects($this->any())->method('getSuperUserId')->willReturn(28);
        $this->companyFactoryMock->expects($this->any())
            ->method('getNonStandardCatalogDistributionList')->willReturn('test@infogain.com');
        $this->companyFactoryMock->expects($this->any())->method('getCompanyName')->willReturn('infogain');
        $this->emailMock->expects($this->once())->method('sendEmail')->willReturnSelf();
        $this->backendHelperMock->expects($this->any())->method('getUrl')
            ->willReturn('https://test.office.fedex.com/stage3fedex7id4w/catalog/product/');
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('load')
            ->willReturnSelf();
        $this->customerFactoryMock->expects($this->any())->method('getSecondaryEmail')
            ->willReturn('abc@test.com');
        $this->customerFactoryMock->expects($this->any())->method('getName')
            ->willReturn('test');
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true); 
        $this->customerRepositoryInterfaceMock->expects($this->any())->method('getById')->willReturn($this->customerFactoryMock);   
        $result = $this->emailHelper->sendReadyForOrderEmailCustomerAdmin($productData);
        $this->assertNotNull($result);
    }

    /**
     * Test method for prepareGenericEmailRequest function
     *
     * @return void
     */
    public function testPrepareReadyForReviewRequest()
    {
        $this->emailMock->expects($this->any())->method('loadEmailTemplate')->willReturn("Test");
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getId')->willReturn(45);
    }
     
    /**
     * Test method for getTemplateId function
     *
     * @return void
     */
    public function testGetTemplateId()
    {
        
        $this->assertEquals('fedex_catalog_item_ready_for_reviw_ca_email_template',
        $this->emailHelper
            ->getTemplateId('fedex_catalog_item_ready_for_reviw_ca_email_template')
        );
    }

    /**
      * Test method for GetSpecialInstruction function
      *
      * @return void
      */
      public function testGetSpecialInstruction()
      {
        $productData = [
            'properties' => [
                '0' => [
                    'id' => 1454950109636,
                    'name' => 'USER_SPECIAL_INSTRUCTIONS',
                    'value' => 'test'
                ]
            ]
        ];
        $result = $this->emailHelper->getSpecialInstruction($productData);

        $this->assertNotNull($result);
      }

    /**
      * Test method for sendCatalogExpirationEmail function
      *
      * @return void
      */
    public function testSendCatalogExpirationEmail()
    {
        $productData = [
            'user_id' => '12,13,14,15,115,151,6',
            'company_id' => 'USER_SPECIAL_INSTRUCTIONS',
            'admin_name' => 'test',
            'to' => 'test',
        ];
        $this->selfRegHelper->expects($this->any())->method('getEmailNotificationAllowUserList')->willReturn([['address'=>'add']]);
        $this->storeManager->expects($this->any())->method('getStore')->willReturnSelf();
        
        $result = $this->emailHelper->sendCatalogExpirationEmail($productData);
        $this->assertNull($result);
    }
    
    /**
     * Test method for getTemplateIdCustomerAdmin function with toggle on
     *
     * @return void
     */
    public function testGetTemplateIdCustomerAdminWithToggleOn()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals('fedex_catalog_item_ready_for_reviw_email_template_without_price', $this->emailHelper->getTemplateIdCustomerAdmin());
    }

    /**
     * Test method for getTemplateIdCustomerAdmin function with toggle off
     *
     * @return void
     */
    public function testGetTemplateIdCustomerAdminToggleOff()
    {
        $this->assertEquals('fedex_catalog_item_ready_for_reviw_email_template', $this->emailHelper->getTemplateIdCustomerAdmin());
    }

    /**
     * Test method for getTemplateIdCustomerAdmin function with toggle on
     *
     * @return void
     */
    public function testGetTemplateIdReadyForOrderCustomerAdminToggleOn()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->assertEquals(
                'fedex_catalog_item_ready_for_order_email_template_without_price',
                $this->emailHelper->getTemplateIdReadyForOrderCustomerAdmin()
        );
    }

    /**
     * Test method for getTemplateIdCustomerAdmin function with toggle off
     *
     * @return void
     */
    public function testGgetTemplateIdReadyForOrderCustomerAdminToggleOff()
    {
        $this->assertEquals(
            'fedex_catalog_item_ready_for_order_email_template',
            $this->emailHelper->getTemplateIdReadyForOrderCustomerAdmin()
        );
    }
}
