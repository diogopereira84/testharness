<?php

namespace Fedex\FXOCMConfigurator\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use Fedex\FXOCMConfigurator\Model\UserworkspaceRepository;
use Fedex\FXOCMConfigurator\Model\Userworkspace as ModelUserworkspace;
use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface;
use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\Data\UserworkspaceSearchResultsInterfaceFactory;
use Fedex\FXOCMConfigurator\Api\UserworkspaceRepositoryInterface;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace as ResourceUserworkspace;
use Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\CollectionFactory as UserworkspaceCollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Api\SearchCriteriaInterface;

class UserworkspaceRepositoryTest extends TestCase
{
    protected $resourceMock;
    protected $userworkspaceFactoryMock;
    /**
     * @var (\Fedex\FXOCMConfigurator\Model\ResourceModel\Userworkspace\CollectionFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $userworkspaceCollectionFactoryMock;
    /**
     * @var (\Fedex\FXOCMConfigurator\Api\Data\UserworkspaceSearchResultsInterfaceFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchResultsFactoryMock;
    /**
     * @var (\Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $collectionProcessorMock;
    /**
     * @var (\Fedex\FXOCMConfigurator\Api\Data\UserworkspaceInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $userworkspaceInterfaceMock;
    /**
     * @var (\Magento\Framework\Model\AbstractModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $abstractModelMock;
    /**
     * @var (\Magento\Framework\Api\SearchCriteriaInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $searchCriteriaMock;
    protected $modelUserworkspaceMock;
    protected $fxocmHelper;
    /**
     * @var UserworkspaceInterfaceFactory
     */
    protected $userworkspaceFactory;

    /**
     * @var Userworkspace
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ResourceUserworkspace
     */
    protected $resource;

    /**
     * @var UserworkspaceCollectionFactory
     */
    protected $userworkspaceCollectionFactory;

    protected const EXCEPTION_MESSAGE = 'exception message';

    protected function setUp(): void
    {
        $objectManager = new ObjectManager($this);


        $this->resourceMock = $this->getMockBuilder(ResourceUserworkspace::class)
            ->setMethods(['save', 'load','delete'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->userworkspaceFactoryMock = $this->getMockBuilder(UserworkspaceInterfaceFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userworkspaceCollectionFactoryMock = $this->getMockBuilder(UserworkspaceCollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchResultsFactoryMock = $this->getMockBuilder(UserworkspaceSearchResultsInterfaceFactory::class)
            ->setMethods(['create', 'setSearchCriteria'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->collectionProcessorMock = $this->getMockBuilder(CollectionProcessorInterface::class)
            ->setMethods(['process'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->userworkspaceInterfaceMock = $this->getMockBuilder(UserworkspaceInterface::class)
            ->setMethods(['getId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->abstractModelMock = $this->getMockBuilder(AbstractModel::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->searchCriteriaMock = $this->getMockBuilder(SearchCriteriaInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->modelUserworkspaceMock = $this->createMock(ModelUserworkspace::class);

        
        
        $this->fxocmHelper = $objectManager->getObject(
            UserworkspaceRepository::class,
            [
                'resource' => $this->resourceMock,
                'userworkspaceFactory' => $this->userworkspaceFactoryMock,
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
        $result = $this->fxocmHelper->save($this->modelUserworkspaceMock);
        $this->assertEquals($this->modelUserworkspaceMock, $result);
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
        $result = $this->fxocmHelper->save($this->modelUserworkspaceMock);
    }

    /**
     * Function to get
     */
    public function testGet()
    {
        $this->userworkspaceFactoryMock->expects($this->any())->method('create')->willReturn($this->modelUserworkspaceMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->modelUserworkspaceMock->expects($this->any())->method('getId')->willReturn(12);
        $result = $this->fxocmHelper->get(12);
        $this->assertEquals($this->modelUserworkspaceMock, $result);
    }

    /**
     * Function to get with exception
     */
    public function testGetException()
    {
        $this->userworkspaceFactoryMock->expects($this->any())->method('create')->willReturn($this->modelUserworkspaceMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->modelUserworkspaceMock->expects($this->any())->method('getId')->willReturn(null);
        $this->expectException(NoSuchEntityException::class);
        $result = $this->fxocmHelper->get(12);
    }

    /**
     * Function to delete
     */
    public function testDelete()
    {
        $this->userworkspaceFactoryMock->expects($this->any())->method('create')->willReturn($this->modelUserworkspaceMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->resourceMock->expects($this->any())->method('delete')->willReturn(null);
        $this->assertTrue($this->fxocmHelper->delete($this->modelUserworkspaceMock));
    }

    /**
     * Function to delete with exception
     */
    public function testDeleteException()
    {
        $this->expectExceptionMessage('Could not delete the userworkspace');
        $this->expectException(CouldNotDeleteException::class);

        $this->userworkspaceFactoryMock->expects($this->any())->method('create')->willReturn($this->modelUserworkspaceMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->resourceMock->expects($this->once())->method('delete')
            ->willThrowException(new \Exception(self::EXCEPTION_MESSAGE));
        $this->fxocmHelper->delete($this->modelUserworkspaceMock);
    }

    /**
     * Function to delete by ID
     */
    public function testDeleteById()
    {
        $this->userworkspaceFactoryMock->expects($this->any())->method('create')->willReturn($this->modelUserworkspaceMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->modelUserworkspaceMock->expects($this->any())->method('getId')->willReturn(12);
        $this->userworkspaceFactoryMock->expects($this->any())->method('create')->willReturn($this->modelUserworkspaceMock);
        $this->resourceMock->expects($this->any())->method('load')->willReturn(null);
        $this->resourceMock->expects($this->any())->method('delete')->willReturn(null);
        $this->fxocmHelper->deleteById(12);
    }
}
