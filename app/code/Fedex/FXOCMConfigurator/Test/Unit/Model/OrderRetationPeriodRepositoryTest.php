<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriodRepository;
use Fedex\FXOCMConfigurator\Model\OrderRetationPeriod as ModelOrderRetationPeriod;
use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface;
use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodSearchResultsInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\OrderRetationPeriodRepositoryInterface;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod as ResourceOrderRetationPeriod;
use Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory as OrderRetationPeriodCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Api\SearchCriteriaInterface;

class OrderRetationPeriodRepositoryTest extends TestCase
{
    protected $resourceMock;
    protected $orderRetationPeriodFactoryMock;
    /**
     * @var (\Fedex\FXOCMConfigurator\Model\ResourceModel\OrderRetationPeriod\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $userworkspaceCollectionFactoryMock;
    /**
     * @var (\Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodSearchResultsInterfaceFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchResultsFactoryMock;
    /**
     * @var (\Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionProcessorMock;
    /**
     * @var (\Fedex\FXOCMConfigurator\Api\Data\OrderRetationPeriodInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $orderRetationPeriodInterfaceMock;
    /**
     * @var (\Magento\Framework\Model\AbstractModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractModelMock;
    /**
     * @var (\Magento\Framework\Api\SearchCriteriaInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchCriteriaMock;
    protected $modelOrderRetationPeriodMock;
    protected $fxocmHelper;
    /**
     * @var OrderRetationPeriodInterfaceFactory
     */
    protected $orderRetationPeriodFactory;

    /**
     * @var OrderRetationPeriod
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ResourceOrderRetationPeriod
     */
    protected $resource;

    /**
     * @var OrderRetationPeriodCollectionFactory
     */
    protected $orderRetationPeriodCollectionFactory;

    protected const EXCEPTION_MESSAGE = 'exception message';

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);


        $this->resourceMock = $this->getMockBuilder(ResourceOrderRetationPeriod::class)
            ->setMethods(['save', 'load','delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderRetationPeriodFactoryMock = $this->getMockBuilder(OrderRetationPeriodInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userworkspaceCollectionFactoryMock = $this->getMockBuilder(OrderRetationPeriodCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchResultsFactoryMock = $this->getMockBuilder(OrderRetationPeriodSearchResultsInterfaceFactory::class)
            ->setMethods(['create', 'setSearchCriteria'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->setMethods(['process'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->orderRetationPeriodInterfaceMock = $this->getMockBuilder(OrderRetationPeriodInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->abstractModelMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->modelOrderRetationPeriodMock = $this->createMock(ModelOrderRetationPeriod::class);

        
        
        $this->fxocmHelper = $objectManager->getObject(
            OrderRetationPeriodRepository::class,
            [
                'resource' => $this->resourceMock,
                'orderRetationPeriodFactory' => $this->orderRetationPeriodFactoryMock,
                'userworkspaceCollectionFactory' => $this->userworkspaceCollectionFactoryMock,
                'searchResultsFactory' => $this->searchResultsFactoryMock,
                'collectionProcessor' => $this->collectionProcessorMock,
            ]
        );
    }

    /**
     * Function to save
     */
    public function testSave()
    {
        $this->resourceMock->expects($this->any())->method('save')->willReturnSelf();
        $result = $this->fxocmHelper->save($this->modelOrderRetationPeriodMock);
        $this->assertEquals($this->modelOrderRetationPeriodMock, $result);
    }

    /**
     * Function to save with exception
     */
    public function testSaveException()
    {
        $this->expectExceptionMessage(self::EXCEPTION_MESSAGE);
        $this->expectException(CouldNotSaveException::class);
        $this->resourceMock->expects($this->any())->method('save')
        ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));
        $result = $this->fxocmHelper->save($this->modelOrderRetationPeriodMock);
    }

    /**
     * Function to get
     */
    public function testGet()
    {
        $this->orderRetationPeriodFactoryMock->expects($this->any())->method('create')->willReturn($this->modelOrderRetationPeriodMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->modelOrderRetationPeriodMock->expects($this->any())->method('getId')->willReturn(12);
        $result = $this->fxocmHelper->get(12);
        $this->assertEquals($this->modelOrderRetationPeriodMock, $result);
    }

    /**
     * Function to get with exception
     */
    public function testGetException()
    {
        $this->orderRetationPeriodFactoryMock->expects($this->any())->method('create')->willReturn($this->modelOrderRetationPeriodMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->modelOrderRetationPeriodMock->expects($this->any())->method('getId')->willReturn(null);
        $this->expectException(NoSuchEntityException::class);
        $result = $this->fxocmHelper->get(12);
    }

    /**
     * Function to delete
     */
    public function testDelete()
    {
        $this->orderRetationPeriodFactoryMock->expects($this->any())->method('create')->willReturn($this->modelOrderRetationPeriodMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->resourceMock->expects($this->any())->method('delete')->willReturn(null);
        $this->assertTrue($this->fxocmHelper->delete($this->modelOrderRetationPeriodMock));
    }

    /**
     * Function to delete with exception
     */
    public function testDeleteException()
    {
        $this->expectExceptionMessage('Could not delete the OrderRetationPeriod');
        $this->expectException(CouldNotDeleteException::class);

        $this->orderRetationPeriodFactoryMock->expects($this->any())->method('create')->willReturn($this->modelOrderRetationPeriodMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->resourceMock->expects($this->once())->method('delete')
            ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));
        $this->fxocmHelper->delete($this->modelOrderRetationPeriodMock);
    }

    /**
     * Function to delete by ID
     */
    public function testDeleteById()
    {
        $this->orderRetationPeriodFactoryMock->expects($this->any())->method('create')->willReturn($this->modelOrderRetationPeriodMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->modelOrderRetationPeriodMock->expects($this->any())->method('getId')->willReturn(12);
        $this->orderRetationPeriodFactoryMock->expects($this->any())->method('create')->willReturn($this->modelOrderRetationPeriodMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->resourceMock->expects($this->any())->method('delete')->willReturn(null);
        $this->fxocmHelper->deleteById(12);
    }
}
