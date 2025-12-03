<?php
/**
 * @category    Fedex
 * @package     Fedex_Customer
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare (strict_types = 1);

namespace Fedex\Customer\Test\Unit\Helper;

use Fedex\Customer\Helper\Customer;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\DB\Adapter\Pdo\Mysql\Interceptor;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\AttributeInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select as DBSelect;
use PHPUnit\Framework\TestCase;

class CustomerTest extends TestCase
{
    protected $moduleDataSetup;
    protected $mysqlInterceptor;
    /**
     * @var (\Magento\Framework\App\Helper\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $context;
    /**
     * @var (\Fedex\EnvironmentManager\ViewModel\ToggleConfig & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $toggleConfig;
    protected $customerFactoryMock;
    protected $storeManager;
    protected $customerMock;
    protected $resourceConnectionMock;
    protected $adapterInterfaceMock;
    protected $dbSelectMock;
    protected $customerRepositoryInterfaceMock;
    protected $customerInterfaceMock;
    protected $attributeInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $customerHelper;
    public const CUSTOMER_FILTERED_EMAIL = 'httpwww.okta.comexk9p9nmi2mDr6K0L5d7_sreejith.b.osv@fedex.com';

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection', 'getTable'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->mysqlInterceptor = $this->getMockBuilder(Interceptor::class)
            ->setMethods(['insertArray', 'lastInsertId', 'update'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();
        $this->customerFactoryMock = $this->getMockBuilder(CustomerFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();
        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getWebsite','getWebsiteId'])
            ->getMockForAbstractClass();
        $this->customerMock = $this->getMockBuilder(\Magento\Customer\Model\Customer::class)
            ->setMethods(['loadByEmail', 'setWebsiteId'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->resourceConnectionMock = $this->getMockBuilder(ResourceConnection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->dbSelectMock = $this->getMockBuilder(DBSelect::class)
            ->setMethods(['from', 'where'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerRepositoryInterfaceMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->onlyMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->customerInterfaceMock = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->attributeInterfaceMock = $this->getMockBuilder(AttributeInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        
        $this->objectManager = new ObjectManager($this);

        $this->customerHelper = $this->objectManager->getObject(
            Customer::class,
            [
                'context' => $this->context,
                'moduleDataSetup' => $this->moduleDataSetup,
                'toggleConfig' => $this->toggleConfig,
                'customerFactory'=> $this->customerFactoryMock,
                'storeManager' => $this->storeManager,
                'resourceConnection' => $this->resourceConnectionMock,
                'customerRepositoryInterface' => $this->customerRepositoryInterfaceMock
            ]
        );
    }

    /**
     * Test updateExternalIdentifier method
     * @return void
     */
    public function testUpdateExternalIdentifier()
    {
        $extId = 'l6site51_neeraj_himkinfogaincom@nol6site51.com';
        $customerId = 23;
        $this->moduleDataSetup->expects($this->any())->method('getConnection')->willReturn($this->mysqlInterceptor);
        $this->moduleDataSetup->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->mysqlInterceptor->expects($this->any())->method('update')->willReturn(3);
        $this->moduleDataSetup->expects($this->any())->method('endSetup')->willReturnSelf();
        $this->assertTrue($this->customerHelper->updateExternalIdentifier($extId, $customerId));
    }
    
    /**
     * Test updateExternalIdentifier method with exception
     * @return void
     */
    public function testUpdateExternalIdentifierWithException()
    {
        $extId = 'l6site51_neeraj_himkinfogaincom@nol6site51.com';
        $customerId = 23;
        $this->moduleDataSetup->expects($this->any())->method('getConnection')->willReturn($this->mysqlInterceptor);
        $this->moduleDataSetup->expects($this->any())->method('startSetup')->willReturnSelf();
        $this->mysqlInterceptor->expects($this->any())->method('update')->willReturn(3);
        $this->moduleDataSetup->expects($this->any())->method('endSetup')->willThrowException(new \Exception());
        $this->assertFalse($this->customerHelper->updateExternalIdentifier($extId, $customerId));
    }

    public function testGetCustomerByUuid()
    {
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->storeManager->expects($this->any())
            ->method('getWebsite')
            ->willReturn($this->storeManager);
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturnSelf();
        $websiteId = 1;
        $this->storeManager->expects($this->any())
            ->method('getWebsiteId')
            ->willReturn($websiteId);
        $this->testCheckCustomerByIdentifier();
        $this->assertSame($this->customerMock, $this->customerHelper->getCustomerByUuid('test@example.com'));
    }

    /**
     * Test getCustomerByUuid
     * @return void
     */
    public function testGetCustomerByUuidNotObject()
    {
        $uuidEmail = 'test@gmail.com';
        $this->customerFactoryMock->expects($this->any())->method('create')->willReturn($this->customerMock);
        $this->storeManager->expects($this->any())->method('getWebsiteId')
        ->willReturn(1);
        $this->customerMock->expects($this->any())->method('setWebsiteId')->willReturnSelf();
        $this->storeManager->expects($this->any())->method('getWebsite')
        ->willReturn($this->storeManager);
        $this->customerMock->expects($this->any())->method('loadByEmail')->willReturn(null);
        $this->testCheckCustomerByIdentifier();
        $this->assertFalse($this->customerHelper->getCustomerByUuid($uuidEmail));
    }

    /**
     * Test checkCustomerByIdentifier
     * @return void
     */
    public function testCheckCustomerByIdentifier()
    {
        $uuidEmail = 'test@gmail.com';
        $this->resourceConnectionMock->expects($this->any())->method('getConnection')
        ->willReturn($this->adapterInterfaceMock);
        $this->adapterInterfaceMock->expects($this->any())->method('getTableName')->willReturn('customer_entity');
        $this->adapterInterfaceMock->expects($this->any())->method('select')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('from')->willReturn($this->dbSelectMock);
        $this->dbSelectMock->expects($this->any())->method('where')->willReturn($this->dbSelectMock);
        $this->adapterInterfaceMock->method('fetchAll')->willReturn([0 => ['email' => $uuidEmail]]);
        $this->assertEquals($uuidEmail, $this->customerHelper->checkCustomerByIdentifier($uuidEmail));
    }

    /**
     * Test getCustomerEmail without @ symbol
     * @return void
     */
    public function testGetCustomerEmail()
    {
        $profileData = [
            'address' => [
                'uuId' => '',
                'customerId' => 'httpwww.okta.comexk9p9nmi2mDr6K0L5d7_sreejith.b.osv'
            ]
        ];
        
        $customerFilteredEmail = static::CUSTOMER_FILTERED_EMAIL;
        $this->assertEquals($customerFilteredEmail, $this->customerHelper->getCustomerEmail($profileData));
    }

    /**
     * Test getCustomerEmail with @ symbol
     * @return void
     */
    public function testGetCustomerEmailWithCorrectFormat()
    {
        $profileData = [
            'address' => [
                'uuId' => '',
                'customerId' => 'httpwww.okta.comexk9p9nmi2mDr6K0L5d7_sreejith.b.osv@fedex.com'
            ]
        ];
        
        $customerFilteredEmail = static::CUSTOMER_FILTERED_EMAIL;
        $this->assertEquals($customerFilteredEmail, $this->customerHelper->getCustomerEmail($profileData));
    }

    /**
     * Test getCustomerStatus
     * @param int $customerStatus
     * @param string $status
     * @param int $customerId
     * @dataProvider getCustomerStatusDataProvider
     * @return void
     */
    public function testGetCustomerStatus($customerStatus, $status, $customerId)
    {
        $this->customerRepositoryInterfaceMock->expects($this->once())
            ->method('getById')
            ->willReturn($this->customerInterfaceMock);

        $this->customerInterfaceMock->expects($this->once())
            ->method('getCustomAttribute')
            ->willReturn($this->attributeInterfaceMock);
        
        $this->attributeInterfaceMock->expects($this->once())
            ->method('getValue')
            ->willReturn($customerStatus);

        $this->assertEquals($status, $this->customerHelper->getCustomerStatus($customerId));
    }

    /**
     * @return array
     */
    public function getCustomerStatusDataProvider(): array
    {
        return [
            [0, 'Inactive', 1],
            [1, 'Active', 2],
            [2, 'Pending For Approval', 3],
            [3, 'Email Verification Pending', 4],
        ];
    }
}
