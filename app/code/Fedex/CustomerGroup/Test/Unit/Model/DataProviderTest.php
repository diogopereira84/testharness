<?php

namespace Fedex\CustomerGroup\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\Registry;
use Fedex\CustomerGroup\Model\DataProvider;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;

class DataProviderTest extends TestCase
{
    /**
     * @var DataProvider
     */
    protected $dataProvider;

    /**
     * @var MockObject
     */
    protected $collectionFactoryMock;

    /**
     * @var MockObject
     */
    protected $coreRegistryMock;

    protected function setUp(): void
    {
        $this->coreRegistryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->collectionFactoryMock = $this->getMockBuilder(CollectionFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataProvider = $this->getMockBuilder(DataProvider::class)
            ->setConstructorArgs(['testDataProvider',
            'customer_group_id','customer_group_id',$this->collectionFactoryMock, $this->coreRegistryMock])
            ->setMethods(['getData'])
            ->getMock();
    }

    public function testGetData()
    {
        $groupId = 1;
        $this->coreRegistryMock->expects($this->any())
        ->method('registry')
        ->with('current_group_id')
        ->willReturn($groupId);
        $reflection = new \ReflectionClass(DataProvider::class);
        $getData = $reflection->getMethod('getData');
        $getData->setAccessible(true);
        $expectedResult = $getData->invoke($this->dataProvider);
        $this->assertNotNull($expectedResult);
    }
}
