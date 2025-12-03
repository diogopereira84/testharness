<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Company\Test\Unit\Controller\Adminhtml\Store;

use Fedex\Company\Controller\Adminhtml\Store\NewStores;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\DB\Helper as DbHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ResourceModel\Group\Collection as GroupCollection;
use Magento\Store\Model\ResourceModel\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Store\Model\ResourceModel\Store\Collection as StoreCollection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for adminhtml company store list controller.
 */
class NewStoresTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $dbHelperMock;
    protected $groupCollectionFactoryMock;
    protected $groupCollectionMock;
    protected $storeCollectionFactoryMock;
    protected $storeCollectionMock;
    protected $requestMock;
    protected $storesMock;
    protected $resultFactory;

    protected function setUp(): void
    {
        $this->dbHelperMock = $this->getMockBuilder(DbHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['escapeLikeValue'])
            ->getMock();

        $this->groupCollectionFactoryMock = $this->getMockBuilder(GroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->groupCollectionMock = $this->getMockBuilder(GroupCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToSelect', 'addFieldToFilter', 'getSize', 'getIterator', 'getData'])
            ->getMock();

        $this->storeCollectionFactoryMock = $this->getMockBuilder(StoreCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->storeCollectionMock = $this->getMockBuilder(StoreCollection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCollection', 'addFieldToSelect', 'addFieldToFilter', 'getSize', 'getIterator', 'getData'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->storesMock = $this->objectManager->getObject(
            NewStores::class,
            [
                'dbHelper' => $this->dbHelperMock,
                'groupCollection' => $this->groupCollectionFactoryMock,
                'storeCollection' => $this->storeCollectionFactoryMock,
                'request' => $this->requestMock,
                'resultFactory' => $this->resultFactory,
            ]
        );
    }

    /**
     * @test testExecuteWithStoreData
     */
    public function testExecuteWithStoreData()
    {
        $storeId = 1;
        $storeViewId = '';
        $storeData = [
            [
                'group_id' => '0',
                'name' => 'Default',
            ],
        ];

        $this->requestMock->method('getParam')
            ->withConsecutive(['name'], ['new_store_id'])
            ->willReturnOnConsecutiveCalls($storeId, $storeViewId);

        $this->dbHelperMock->expects($this->any())
            ->method('escapeLikeValue')->willReturn($storeId);
        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->resultFactory->method('create')
            ->withConsecutive([])
            ->willReturnOnConsecutiveCalls($result);

        $this->groupCollectionFactoryMock->method('create')
            ->withConsecutive([])
            ->willReturnOnConsecutiveCalls($this->groupCollectionMock);

        $this->groupCollectionMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->groupCollectionMock);

        $this->groupCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();
        $this->groupCollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($storeData);

        $this->assertEquals($result, $this->storesMock->execute());
    }

    /**
     * @test testExecuteWithStoreViewData
     */
    public function testExecuteWithStoreViewData()
    {
        $storeId = 1;
        $storeViewId = 1;
        $storeViewData = [
            [
                'store_id' => '0',
                'name' => 'Default',
            ],
        ];

        $this->requestMock->method('getParam')
            ->withConsecutive(['name'], ['new_store_id'])
            ->willReturnOnConsecutiveCalls($storeViewId, $storeId);

        $this->dbHelperMock->expects($this->any())
            ->method('escapeLikeValue')->willReturn($storeViewId);

        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->resultFactory->method('create')
            ->withConsecutive([])
            ->willReturnOnConsecutiveCalls($result);

        $this->storeCollectionFactoryMock->method('create')
            ->withConsecutive([])
            ->willReturnOnConsecutiveCalls($this->storeCollectionMock);

        $this->storeCollectionMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->storeCollectionMock);

        $this->storeCollectionMock->expects($this->any())
            ->method('addFieldToFilter')
            ->willReturnSelf();

        $this->storeCollectionMock->expects($this->any())
            ->method('getData')
            ->willReturn($storeViewData);

        $this->assertEquals($result, $this->storesMock->execute());
    }

    /**
     * @test testExecuteWithException
     */
    public function testExecuteWithException()
    {
        $storeId = 1;
        $storeViewId = 1;

        $this->requestMock->method('getParam')
            ->withConsecutive(['name'], ['new_store_id'])
            ->willReturnOnConsecutiveCalls($storeViewId, $storeId);

        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);

        $this->storeCollectionFactoryMock->method('create')
            ->withConsecutive([])
            ->willReturnOnConsecutiveCalls($this->throwException($exception));

        $result = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['setData'])
            ->getMock();

        $this->resultFactory->method('create')
            ->withConsecutive([])
            ->willReturnOnConsecutiveCalls($result);

        $this->assertEquals($result, $this->storesMock->execute());
    }
}
