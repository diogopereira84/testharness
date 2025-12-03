<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CustomerExportEmail\Test\Unit\Helper;


use Fedex\CustomerExportEmail\Helper\Data;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Ui\Model\BookmarkManagement;
use Magento\User\Model\UserFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Customer\Model\AttributeValue;
use Magento\Customer\Api\Data\CustomerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

/**
 * Test class for GenerateCsvHelper
 */
class DataTest extends TestCase
{
    protected $groupRepositoryMock;
    protected $addressRepositoryMock;
    protected $dataAddressInterfaceMock;
    protected $regionInterfaceMock;
    protected $companyRepositoryMock;
    protected $customerRepositoryMock;
    protected $attributeValueMock;
    protected $customer;
    protected $bookmarkManagementMock;
    protected $userFactoryMock;
    protected $websiteRepositoryMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManagerHelper;
    protected $dataHelperMock;
    public const EXCEL_DIR_PATH = 'customerdata_export';

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var Write
     */
    protected $directoryWriteMock;

    /**
     * @var ToggleConfig
     */
    private ToggleConfig $toggleConfigMock;

    /**
     * Set up method.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->directoryList = $this->getMockBuilder(DirectoryList::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPath'])
            ->getMock();

        $this->filesystem = $this->createMock(Filesystem::class);

        $this->directoryWriteMock = $this->getMockBuilder(Write::class)
            ->disableOriginalConstructor()
            ->setMethods(['isDirectory', 'lock', 'create', 'openFile', 'writeCsv'])
            ->getMock();

        $this->filesystem->expects($this->any())->method('getDirectoryWrite')->willReturn($this->directoryWriteMock);

        $this->groupRepositoryMock = $this->getMockBuilder(GroupRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','getCode'])
            ->getMockForAbstractClass();

        $this->addressRepositoryMock = $this->getMockBuilder(AddressRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();

        $this->dataAddressInterfaceMock = $this->getMockBuilder(AddressInterface::class)
            ->setMethods(['getRegion', 'getStreet', 'getCity', 'getPostcode',
                'getCountryId', 'getTelePhone', 'getFirstname', 'getLastname','getFax'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->regionInterfaceMock = $this->getMockBuilder(RegionInterface::class)
            ->setMethods(['getRegion'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyManagementInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByCustomerId','getCompanyName','getSalesRepresentativeId'])
            ->getMockForAbstractClass();

        $this->customerRepositoryMock = $this->getMockBuilder(CustomerRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById'])
            ->getMockForAbstractClass();


        $this->attributeValueMock = $this->getMockBuilder(AttributeValue::class)
            ->disableOriginalConstructor()
            ->setMethods(['getValue'])
            ->getMock();

        $this->customer = $this->getMockBuilder(CustomerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getCustomAttribute'])
            ->getMockForAbstractClass();


        $this->bookmarkManagementMock = $this->getMockBuilder(BookmarkManagement::class)
            ->disableOriginalConstructor()
            ->setMethods(['getByIdentifierNamespace','getConfig'])
            ->getMock();

        $this->userFactoryMock = $this->getMockBuilder(UserFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create','load','getEmail'])
            ->getMock();

        $this->websiteRepositoryMock = $this->getMockBuilder(WebsiteRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getById','getName'])
            ->getMockForAbstractClass();

        // Create a mock for ToggleConfig
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);

        $this->objectManagerHelper = new ObjectManager($this);

        $this->dataHelperMock = $this->objectManagerHelper->getObject(
            Data::class,
            [
                'context' => $this->context,
                'directoryList' => $this->directoryList,
                'filesystem' => $this->filesystem,
                'directory' => $this->directoryWriteMock,
                'groupRepository' => $this->groupRepositoryMock,
                'addressRepository' => $this->addressRepositoryMock,
                'companyRepository' => $this->companyRepositoryMock,
                'customerRepository' => $this->customerRepositoryMock,
                'bookmarkManagement' => $this->bookmarkManagementMock,
                'userFactory' => $this->userFactoryMock,
                'websiteRepository' => $this->websiteRepositoryMock,
                'toggleConfig' => $this->toggleConfigMock
            ]
        );

        $this->dataHelperMock->customerData = [[
            'entity_id' => 1,
            'firstname' => 'Test',
            'lastname' => 'Test',
            'email' => 'test@test.com',
            'created_in' => 'Ondemand',
            'dob' => '',
            'taxvat' => '',
            'gender' => '',
            'default_billing' => 23,
            'group_id' => 2,
            'is_active' => 1,
            'website_id' => 1,
            'created_at' => '2023-4-4 10:00AM'
        ]];

    }

    /**
     * Test method for generateExcel
     *
     * @return void
     */
    public function testGenerateExcel()
    {
        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');

        $headerDataArray = ['Account Name', 'FXK Account Number'];
        $rowDataArray = ['test', 'data'];
        $fileName = 'Customerdata_22222222222_20230731_224125.csv';

        $this->assertNotNull(
            $this->dataHelperMock->
                generateExcel($headerDataArray, $rowDataArray, $fileName, self::EXCEL_DIR_PATH)
        );
    }

    /**
     * Test method for customer data csv
     *
     * @return void
     */
    public function testGenerateCustomerDataCsv()
    {
        $inactiveColumns = ['email', 'group_id', 'billing_postcode'];

        $this->groupRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->groupRepositoryMock->expects($this->any())->method('getCode')->willReturn('General');

        $this->addressRepositoryMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getRegion')->willReturn($this->regionInterfaceMock);

        $this->regionInterfaceMock->expects($this->any())->method('getRegion')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getStreet')->willReturn(['Test']);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getPostcode')->willReturn('123123');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCity')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCountryId')->willReturn('US');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getTelePhone')->willReturn('123213');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFirstname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getLastname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFax')->willReturn('');

        $this->companyRepositoryMock->expects($this->any())->method('getByCustomerId')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getCompanyName')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getSalesRepresentativeId')->willReturnSelf();

        $this->userFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('getEmail')->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);

        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(true);

        $this->websiteRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->websiteRepositoryMock->expects($this->any())->method('getName')->willReturnSelf();

        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(false);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');
        
        $this->assertNotNull(
            $this->dataHelperMock->
                generateCustomerDataCsv($this->dataHelperMock->customerData, $inactiveColumns)
        );
    }

    /**
     * Test method for customer data csv
     *
     * @return void
     */
    public function testGenerateCustomerDataCsvwithPendingApproval()
    {
        $inactiveColumns = ['email', 'group_id', 'billing_postcode'];

        $this->groupRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->groupRepositoryMock->expects($this->any())->method('getCode')->willReturn('General');

        $this->addressRepositoryMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getRegion')->willReturn($this->regionInterfaceMock);

        $this->regionInterfaceMock->expects($this->any())->method('getRegion')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getStreet')->willReturn(['Test']);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getPostcode')->willReturn('123123');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCity')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCountryId')->willReturn('US');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getTelePhone')->willReturn('123213');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFirstname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getLastname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFax')->willReturn('');

        $this->companyRepositoryMock->expects($this->any())->method('getByCustomerId')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getCompanyName')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getSalesRepresentativeId')->willReturnSelf();

        $this->userFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('getEmail')->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);

        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(2);

        $this->websiteRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->websiteRepositoryMock->expects($this->any())->method('getName')->willReturnSelf();

        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(false);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');
        
        $this->assertNotNull(
            $this->dataHelperMock->
                generateCustomerDataCsv($this->dataHelperMock->customerData, $inactiveColumns)
        );
    }

    /**
     * Test method for customer data csv active customer
     *
     * @return void
     */
    public function testGenerateCustomerDataCsvwithActive()
    {
        $inactiveColumns = ['email', 'group_id', 'billing_postcode'];

        $this->groupRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->groupRepositoryMock->expects($this->any())->method('getCode')->willReturn('General');

        $this->addressRepositoryMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getRegion')->willReturn($this->regionInterfaceMock);

        $this->regionInterfaceMock->expects($this->any())->method('getRegion')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getStreet')->willReturn(['Test']);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getPostcode')->willReturn('123123');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCity')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCountryId')->willReturn('US');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getTelePhone')->willReturn('123213');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFirstname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getLastname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFax')->willReturn('');

        $this->companyRepositoryMock->expects($this->any())->method('getByCustomerId')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getCompanyName')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getSalesRepresentativeId')->willReturnSelf();

        $this->userFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('getEmail')->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);

        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(1);

        $this->websiteRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->websiteRepositoryMock->expects($this->any())->method('getName')->willReturnSelf();

        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(false);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');
        
        $this->assertNotNull(
            $this->dataHelperMock->
                generateCustomerDataCsv($this->dataHelperMock->customerData, $inactiveColumns)
        );
    }

    /**
     * Test method for customer data csv
     *
     * @return void
     */
    public function testGenerateCustomerDataCsvwithInactive()
    {
        $inactiveColumns = ['email', 'group_id', 'billing_postcode'];

        $this->groupRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->groupRepositoryMock->expects($this->any())->method('getCode')->willReturn('General');

        $this->addressRepositoryMock->expects($this->any())->method('getById')->willReturn($this->dataAddressInterfaceMock);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getRegion')->willReturn($this->regionInterfaceMock);

        $this->regionInterfaceMock->expects($this->any())->method('getRegion')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getStreet')->willReturn(['Test']);

        $this->dataAddressInterfaceMock->expects($this->any())->method('getPostcode')->willReturn('123123');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCity')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getCountryId')->willReturn('US');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getTelePhone')->willReturn('123213');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFirstname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getLastname')->willReturn('Test');

        $this->dataAddressInterfaceMock->expects($this->any())->method('getFax')->willReturn('');

        $this->companyRepositoryMock->expects($this->any())->method('getByCustomerId')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getCompanyName')->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())->method('getSalesRepresentativeId')->willReturnSelf();

        $this->userFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('load')->willReturnSelf();
        $this->userFactoryMock->expects($this->any())->method('getEmail')->willReturnSelf();

        $this->customerRepositoryMock->expects($this->any())->method('getById')->willReturn($this->customer);

        $this->customer->expects($this->any())->method('getCustomAttribute')->willReturn($this->attributeValueMock);

        $this->attributeValueMock->expects($this->any())->method('getValue')->willReturn(false);

        $this->websiteRepositoryMock->expects($this->any())->method('getById')->willReturnSelf();

        $this->websiteRepositoryMock->expects($this->any())->method('getName')->willReturnSelf();

        $this->directoryWriteMock->expects($this->any())->method('isDirectory')->willReturn(false);
        $this->directoryWriteMock->expects($this->any())->method('create')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('openFile')->willReturnSelf();
        $this->directoryWriteMock->expects($this->any())->method('lock')->willReturn(true);
        $this->directoryWriteMock->expects($this->any())->method('writeCsv')->willReturn(true);
        $this->directoryList->expects($this->any())->method('getPath')->willReturn('/' . self::EXCEL_DIR_PATH . '/');
        
        $this->assertNotNull(
            $this->dataHelperMock->
                generateCustomerDataCsv($this->dataHelperMock->customerData, $inactiveColumns)
        );
    }

    /**
     * Test GetInActiveColumns
     * 
     */
    public function testGetInActiveColumns()
    {
    	$this->bookmarkManagementMock->expects($this->any())->method('getByIdentifierNamespace')->willReturnSelf();
    	$config = [
					    "current" => [
					        "columns" => [
					            "entity_id" => ["visible" => true, "sorting" => "asc"],
					            "name" => ["visible" => true, "sorting" => false],
					            "email" => ["visible" => true, "sorting" => false],
					            "billing_telephone" => ["visible" => false, "sorting" => false],
					            "billing_postcode" => ["visible" => false, "sorting" => false],
					            "billing_region" => ["visible" => true, "sorting" => false]
					        ]
					    ],
				   ];
    	$this->bookmarkManagementMock->expects($this->any())->method('getConfig')->willReturn($config);
    	$this->dataHelperMock->getInActiveColumns();

    }

    /**
     * Test method for customerExportIssueFixToggleEnabled
     *
     * @return void
     */
    public function testCustomerExportIssueFixToggleEnabledReturnsTrue(): void
    {
        $this->toggleConfigMock
            ->expects($this->once())
            ->method('getToggleConfigValue')
            ->with('techtitans_D180778_fix')
            ->willReturn(true);

        $this->assertTrue($this->dataHelperMock->customerExportIssueFixToggleEnabled());
    }
}
