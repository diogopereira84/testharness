<?php

namespace Fedex\MarketplacePunchout\Test\Unit\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueIdRepository;
use Fedex\MarketplacePunchout\Model\CustomerPunchoutUniqueIdFactory;
use Fedex\MarketplacePunchout\Model\ResourceModel\CustomerPunchoutUniqueId\CollectionFactory;
use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdSearchResultInterfaceFactory;
use Fedex\MarketplacePunchout\Api\Data\CustomerPunchoutUniqueIdSearchResultInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Math\Random;
use Fedex\MarketplacePunchout\Model\CustomerPunchoutUniqueId;
use Magento\Customer\Model\Customer;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Data\Collection\AbstractDb as Collection;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Psr\Log\LoggerInterface;

class CustomerPunchoutUniqueIdRepositoryTest extends TestCase
{
    protected $resource;
    private $customerPunchoutUniqueIdFactory;
    private $customerPunchoutUniqueIdCollectionFactory;
    private $customerPunchoutUniqueIdSearchResultInterfaceFactory;
    private $collectionProcessor;
    private $random;
    private $loggerInterface;
    private $repository;

    protected function setUp(): void
    {
        $this->customerPunchoutUniqueIdFactory = $this->createMock(CustomerPunchoutUniqueIdFactory::class);
        $this->customerPunchoutUniqueIdCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->customerPunchoutUniqueIdSearchResultInterfaceFactory = $this->createMock(CustomerPunchoutUniqueIdSearchResultInterfaceFactory::class);
        $this->collectionProcessor = $this->createMock(CollectionProcessorInterface::class);
        $this->random = $this->createMock(Random::class);
        $this->loggerInterface = $this->createMock(LoggerInterface::class);
        $this->resource = $this->createMock(AbstractDb::class);

        $this->repository = new CustomerPunchoutUniqueIdRepository(
            $this->customerPunchoutUniqueIdFactory,
            $this->customerPunchoutUniqueIdCollectionFactory,
            $this->customerPunchoutUniqueIdSearchResultInterfaceFactory,
            $this->collectionProcessor,
            $this->random,
            $this->loggerInterface
        );
    }

    public function testGetById()
    {
        $id = 1;
        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getId')->willReturn($id);
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->createMock(AbstractDb::class));

        $this->customerPunchoutUniqueIdFactory->method('create')->willReturn($customerPunchoutUniqueId);

        $result = $this->repository->getById($id);
        $this->assertSame($customerPunchoutUniqueId, $result);
    }

    public function testGetByIdThrowsException()
    {
        $this->expectException(NoSuchEntityException::class);

        $id = 1;
        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getId')->willReturn(null);
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->createMock(AbstractDb::class));

        $this->customerPunchoutUniqueIdFactory->method('create')->willReturn($customerPunchoutUniqueId);

        $this->repository->getById($id);
    }

    public function testSave()
    {
        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->createMock(AbstractDb::class));

        $result = $this->repository->save($customerPunchoutUniqueId);
        $this->assertSame($customerPunchoutUniqueId, $result);
    }

    public function testDelete()
    {
        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->createMock(AbstractDb::class));

        $this->repository->delete($customerPunchoutUniqueId);
        $this->assertTrue(true);
    }

    public function testGetList()
    {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $collection = $this->createMock(Collection::class);
        $searchResult = $this->createMock(CustomerPunchoutUniqueIdSearchResultInterface::class);

        $this->customerPunchoutUniqueIdCollectionFactory->method('create')->willReturn($collection);
        $this->customerPunchoutUniqueIdSearchResultInterfaceFactory->method('create')->willReturn($searchResult);

        $collection->expects($this->once())->method('getItems')->willReturn([]);
        $collection->expects($this->once())->method('getSize')->willReturn(0);

        $searchResult->expects($this->once())->method('setSearchCriteria')->with($searchCriteria);
        $searchResult->expects($this->once())->method('setItems')->with([]);
        $searchResult->expects($this->once())->method('setTotalCount')->with(0);

        $this->collectionProcessor->expects($this->once())
            ->method('process')
            ->with($searchCriteria, $collection);

        $result = $this->repository->getList($searchCriteria);
        $this->assertSame($searchResult, $result);
    }

    public function testRetrieveCustomerUniqueId()
    {
        $customerId = 1;
        $uniqueId = 'unique123';
        $customer = $this->createMock(Customer::class);
        $customer->method('getId')->willReturn($customerId);

        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getUniqueId')->willReturn($uniqueId);
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->resource);

        $this->customerPunchoutUniqueIdFactory->method('create')->willReturn($customerPunchoutUniqueId);

        $this->resource->method('load')
            ->with($customerPunchoutUniqueId, $customerId)
            ->willReturn($customerPunchoutUniqueId);

        $result = $this->repository->retrieveCustomerUniqueId($customer);
        $this->assertEquals($uniqueId, $result);
    }

    public function testRetrieveCustomerUniqueIdCreatesNewId()
    {
        $customerId = 1;
        $uniqueId = 'unique123';
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getEmail'])
            ->getMock();
        $customer->method('getId')->willReturn($customerId);
        $customer->method('getEmail')->willReturn('test@example.com');

        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getUniqueId')->willReturn($uniqueId);
        $customerPunchoutUniqueId->expects($this->once())->method('setCustomerId')->with($customerId);
        $customerPunchoutUniqueId->expects($this->once())->method('setCustomerEmail')->with('test@example.com');
        $customerPunchoutUniqueId->expects($this->once())->method('setUniqueId');
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->resource);


        $this->customerPunchoutUniqueIdFactory->method('create')->willReturn($customerPunchoutUniqueId);

        $this->resource->method('load')
            ->with($customerPunchoutUniqueId, $customerId)
            ->willReturn($customerPunchoutUniqueId);

        $this->random->method('getRandomString')->willReturn(substr($uniqueId, strlen($customerId)));

        $result = $this->repository->retrieveCustomerUniqueId($customer);
        $this->assertEquals($uniqueId, $result);
    }

    public function testRetrieveCustomerUniqueIdException()
    {
        $customerId = 1;
        $uniqueId = 'unique123';
        $customer = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getId'])
            ->addMethods(['getEmail'])
            ->getMock();
        $customer->method('getId')->willReturn($customerId);
        $customer->method('getEmail')->willReturn('test@example.com');

        $customerPunchoutUniqueId = $this->createMock(CustomerPunchoutUniqueId::class);
        $customerPunchoutUniqueId->method('getUniqueId')->willReturn($uniqueId);
        $customerPunchoutUniqueId->expects($this->once())->method('setCustomerId')->with($customerId);
        $customerPunchoutUniqueId->expects($this->once())->method('setCustomerEmail')->with('test@example.com');
        $customerPunchoutUniqueId->expects($this->once())->method('setUniqueId');
        $customerPunchoutUniqueId->method('getResource')->willReturn($this->resource);

        $this->customerPunchoutUniqueIdFactory->method('create')->willReturn($customerPunchoutUniqueId);
        $this->random->method('getRandomString')->willReturn(substr($uniqueId, strlen($customerId)));

        $this->resource->method('save')
            ->with($customerPunchoutUniqueId)
            ->willThrowException(new \Exception(__('Error')));

        $this->loggerInterface->expects($this->once())
            ->method('error')
            ->with('Couldn\'t setup Unique ID for Customer: '.$customerId.'. Error: Error');

        $return = $this->repository->retrieveCustomerUniqueId($customer);
        $this->assertEquals('', $return);
    }
}
