<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\Company\Test\Unit\Model\Config\Source;

use ArrayIterator;
use Fedex\Company\Model\Config\Source\States;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Directory\Model\ResourceModel\Region\Collection;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class StatesTest extends TestCase
{
    protected $regionFactoryMock;
    protected $regionMock;
    protected $regionCollectionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $model;
    protected function setUp(): void
    {
        $this->regionFactoryMock = $this->getMockBuilder(RegionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionMock = $this->getMockBuilder(Region::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->regionCollectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['addFieldToFilter', 'getIterator'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);
        $this->model = $this->objectManager->getObject(
            States::class,
            ['regionFactory' => $this->regionFactoryMock]
        );
    }

    /**
     * @test testToOptionArray
     */
    public function testToOptionArray()
    {
        $this->regionFactoryMock->expects($this->any())
            ->method('create')
            ->willReturn($this->regionMock);

        $this->regionMock->expects($this->any())
            ->method('getCollection')
            ->willReturn($this->regionCollectionMock);

        $this->regionCollectionMock->expects($this->any())
            ->method('addFieldToFilter')->willReturnSelf();

        $this->regionCollectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$this->regionMock]));

        $this->model->toOptionArray();
    }
}
