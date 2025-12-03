<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SelfReg\Test\Unit\Model\Group;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;
use Fedex\SelfReg\Model\ResourceModel\UserGroups\CollectionFactory;
use Magento\Framework\App\RequestInterface;
use Fedex\SelfReg\Model\Group\DataProvider;


class DataProviderTest extends TestCase
{
    protected $collectionFactory;
    protected $request;
    protected $dataProviderMock;

    protected function setUp(): void
    {

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['getSelect', 'reset', 'join', 'getTable', 'where','joinLeft','columns','group','getItems'])
            ->getMock();

        $objectManagerHelper = new ObjectManager($this);

        $this->dataProviderMock = $objectManagerHelper->getObject(
            DataProvider::class,
            [
                'request' => $this->request,
                'collection' => $this->collectionFactory,
            ]
        );
    }

    public function testGetDataIf()
    {
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturn('123');
        $this->collectionFactory->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('reset')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('join')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('getTable')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('joinLeft')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('columns')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('group')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('getItems')
            ->willReturnSelf();
            
       $this->assertNotNull($this->dataProviderMock->getData());
    }

    public function testGetDataElse()
    {
        $this->request->expects($this->any())
        ->method('getParam')
        ->willReturn(null);
        $this->collectionFactory->expects($this->any())
            ->method('getSelect')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('reset')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('join')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('getTable')
            ->willReturnSelf();
        $this->collectionFactory->expects($this->any())
            ->method('where')
            ->willReturnSelf();
        $this->assertNotNull($this->dataProviderMock->getData());
    }
}
