<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Model;

use Exception;
use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\Company\Model\AdditionalData;
use Fedex\Company\Model\AdditionalDataFactory;
use Fedex\Company\Model\CompanyData;
use Fedex\Company\Model\ResourceModel\AdditionalData\Collection as AdditionalDataCollection;
use Fedex\Ondemand\Api\Data\ConfigInterface as OndemandConfigInterface;
use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Company\Api\Data\CompanyInterface;
use Magento\Company\Api\Data\CompanySearchResultsInterface;
use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CompanyDataTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Model\Context & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $contextMock;
    /**
     * @var (\Magento\Framework\Registry & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $registryMock;
    protected $additionalDataFactoryMock;
    protected $additionalDataMock;
    protected $additionalDataCollectionMock;
    /**
     * @var (\Magento\Framework\Model\ResourceModel\AbstractResource & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceMock;
    /**
     * @var (\Magento\Framework\Data\Collection\AbstractDb & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $resourceCollectionMock;
    protected $companyInterfaceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $searchCriteriaBuilderMock;
    protected $companyRepositoryMock;
    protected $filterBuilderMock;
    protected $filterMock;
    protected $toggleConfigMock;
    protected $searchResultMock;
    protected $searchCriteriaMock;
    /**
     * @var (\Fedex\Company\Api\Data\ConfigInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $configInterfaceMock;
    protected $ondemandConfigInterfaceMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dataProvider;
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataFactoryMock = $this->getMockBuilder(AdditionalDataFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataMock = $this->getMockBuilder(AdditionalData::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->additionalDataCollectionMock = $this
            ->getMockBuilder(AdditionalDataCollection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceMock = $this->getMockBuilder(AbstractResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyInterfaceMock = $this->getMockBuilder(CompanyInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->companyRepositoryMock = $this->getMockBuilder(CompanyRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'setField',
                    'setValue',
                    'setConditionType',
                    'create',
                ]
            )
            ->getMock();

        $this->filterMock = $this->getMockBuilder(Filter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->setMethods(['getToggleConfigValue'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->searchResultMock = $this->getMockForAbstractClass(CompanySearchResultsInterface::class);

        $this->searchCriteriaMock = $this->createMock(SearchCriteria::class);
        $this->configInterfaceMock = $this->createMock(ConfigInterface::class);
        $this->ondemandConfigInterfaceMock = $this->createMock(OndemandConfigInterface::class);

        $this->objectManager = new ObjectManager($this);
        $this->dataProvider = $this->objectManager->getObject(
            CompanyData::class,
            [
                'context' => $this->contextMock,
                'registry' => $this->registryMock,
                'additionalDataFactory' => $this->additionalDataFactoryMock,
                'resource' => $this->resourceMock,
                'resourceCollection' => $this->resourceCollectionMock,
                '_logger' => $this->loggerMock,
                'companyRepository' => $this->companyRepositoryMock,
                'searchCriteriaBuilder' => $this->searchCriteriaBuilderMock,
                'filterBuilder' =>$this->filterBuilderMock,
                'toggleConfig' => $this->toggleConfigMock,
                'configInterface' => $this->configInterfaceMock,
                'ondemandConfigInterface' => $this->ondemandConfigInterfaceMock,
                'data' => [],
            ]
        );
    }

    /**
     * @test testGetAdditionalData
     */
    public function testGetAdditionalData()
    {
        $testData = [
            'id' => 1,
            'company_id' => 31,
            'store_view_id' => 65,
            'store_id' => 1,
            'store_name' => 'test',
            'store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
        ];

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getData')
            ->willReturn($testData);

        $this->assertEquals($testData, $this->dataProvider->getAdditionalData($this->companyInterfaceMock));
    }

    /**
     * @test testGetAdditionalDataWithEmptyData
     */
    public function testGetAdditionalDataWithEmptyData()
    {
        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn(null);

        $this->assertEquals([], $this->dataProvider->getAdditionalData($this->companyInterfaceMock));
    }

    /**
     * @test testGetAdditionalDataWithException
     */
    public function testGetAdditionalDataWithException()
    {
        //throw exception
        $phrase = new Phrase(__('Exception message'));
        $exception = new Exception($phrase);

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willThrowException($exception);

        $this->assertEquals([], $this->dataProvider->getAdditionalData($this->companyInterfaceMock));
    }

    /**
     * @test testGetCompanyIdByCustomerGroup
     */
    public function testGetCompanyIdByCustomerGroup()
    {
        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$this->filterMock]);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->searchCriteriaMock->expects($this->any())
            ->method('setPageSize')
            ->willReturnSelf();

        $this->searchCriteriaMock->expects($this->any())
            ->method('setCurrentPage')
            ->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->searchResultMock);

        $this->searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->companyInterfaceMock]);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(31);

        $this->assertEquals(31, $this->dataProvider->getCompanyIdByCustomerGroup(4));
    }

    /**
     * @test testGetCompanyIdByCustomerGroupWithException
     */
    public function testGetCompanyIdByCustomerGroupWithException()
    {
        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$this->filterMock]);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->searchCriteriaMock->expects($this->any())
            ->method('setPageSize')
            ->willReturnSelf();

        $this->searchCriteriaMock->expects($this->any())
            ->method('setCurrentPage')
            ->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->searchResultMock);

        $this->searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn($this->companyInterfaceMock);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(31);

        $this->assertEquals(null, $this->dataProvider->getCompanyIdByCustomerGroup(4));
    }

    public function testGetStoreViewIdByCustomerGroup()
    {
        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$this->filterMock]);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->searchCriteriaMock->expects($this->any())
            ->method('setPageSize')
            ->willReturnSelf();

        $this->searchCriteriaMock->expects($this->any())
            ->method('setCurrentPage')
            ->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->searchResultMock);

        $this->searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->companyInterfaceMock]);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(31);

        $testData = [
            'id' => 1,
            'company_id' => 31,
            'store_view_id' => 65,
            'store_id' => 1,
            'store_name' => 'test',
            'store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
            'new_store_view_id'=> 65
        ];

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getData')
            ->willReturn($testData);

        $this->ondemandConfigInterfaceMock->expects($this->once())
            ->method('getB2bDefaultStore')
            ->willReturn(65);

        $this->assertEquals(65, $this->dataProvider->getStoreViewIdByCustomerGroup(4));
    }

    /**
     * @test testGetStoreViewIdByCustomerGroupWithEmptyCustomerData
     */
    public function testGetStoreViewIdByCustomerGroupWithEmptyCustomerData()
    {
        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$this->filterMock]);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->searchCriteriaMock->expects($this->any())
            ->method('setPageSize')
            ->willReturnSelf();

        $this->searchCriteriaMock->expects($this->any())
            ->method('setCurrentPage')
            ->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->searchResultMock);

        $this->searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->companyInterfaceMock]);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(31);

        $testData = [];

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getData')
            ->willReturn($testData);

        $this->assertEquals(null, $this->dataProvider->getStoreViewIdByCustomerGroup(4));
    }

    public function testCustomerGroupWithToggleRestructureToggleON()
    {
        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setField')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setConditionType')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('setValue')
            ->willReturnSelf();

        $this->filterBuilderMock->expects($this->exactly(1))
            ->method('create')
            ->willReturn($this->filterMock);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('addFilters')
            ->with([$this->filterMock]);

        $this->searchCriteriaBuilderMock->expects($this->any())
            ->method('create')
            ->willReturn($this->searchCriteriaMock);

        $this->searchCriteriaMock->expects($this->any())
            ->method('setPageSize')
            ->willReturnSelf();

        $this->searchCriteriaMock->expects($this->any())
            ->method('setCurrentPage')
            ->willReturnSelf();

        $this->companyRepositoryMock->expects($this->any())
            ->method('getList')
            ->with($this->searchCriteriaMock)
            ->willReturn($this->searchResultMock);

        $this->searchResultMock->expects($this->any())
            ->method('getItems')
            ->willReturn([$this->companyInterfaceMock]);

        $this->companyInterfaceMock->expects($this->any())
            ->method('getId')
            ->willReturn(31);

        $testData = [
            'id' => 1,
            'company_id' => 31,
            'store_view_id' => 65,
            'new_store_view_id' => 65,
            'store_id' => 1,
            'store_name' => 'test',
            'store_view_name' => 'test',
            'cc_token' => '',
            'cc_data' => '',
            'company_payment_options' => ["fedexaccountnumber"],
            'creditcard_options' => '',
            'fedex_account_options' => 'legacyaccountnumber',
            'default_payment_method' => '',
        ];

        $this->additionalDataFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->additionalDataMock);

        $this->additionalDataMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->additionalDataCollectionMock);

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToSelect')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->additionalDataCollectionMock->expects($this->any())
            ->method('getFirstItem')
            ->willReturn($this->additionalDataMock);

        $this->toggleConfigMock->expects($this->any())
            ->method('getToggleConfigValue')
            ->willReturn(true);

        $this->additionalDataMock->expects($this->any())
            ->method('getData')
            ->willReturn($testData);

        $this->ondemandConfigInterfaceMock->expects($this->once())
            ->method('getB2bDefaultStore')
            ->willReturn(65);

        $this->assertEquals(65, $this->dataProvider->getStoreViewIdByCustomerGroup(4));
    }
}
