<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Unit\Plugin;

use ArrayIterator;
use Exception;
use Fedex\ProductEngine\Plugin\Collection;
use Fedex\Company\Api\Data\ConfigInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as CoreOptionCollection;
use Magento\Framework\DataObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * @covers \Fedex\ProductEngine\Plugin\Collection
 */
class CollectionTest extends TestCase
{
    private Collection $plugin;

    protected function setUp(): void
    {
        $configMock = $this->createMock(ConfigInterface::class);
        $this->plugin = new Collection($configMock);
    }

    /**
     * Test _toOptionArray returns empty array for empty collection
     */
    public function testToOptionArrayEmptyCollection(): void
    {
        $collectionMock = $this->getMockBuilder(CoreOptionCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([]));

        $reflection = new ReflectionClass($this->plugin);
        $method = $reflection->getMethod('_toOptionArray');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin, $collectionMock, 'id', 'name', []);
        $this->assertSame([], $result);
    }

    /**
     * Test _toOptionArray with item missing fields
     * @throws ReflectionException
     */
    public function testToOptionArrayItemMissingFields(): void
    {
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->method('getData')->willReturnCallback(function ($field) {
            return $field === 'id' ? 1 : null;
        });

        $collectionMock = $this->getMockBuilder(CoreOptionCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$itemMock]));

        $reflection = new ReflectionClass($this->plugin);
        $method = $reflection->getMethod('_toOptionArray');
        $method->setAccessible(true);

        $result = $method->invoke($this->plugin, $collectionMock, 'id', 'name', []);
        $this->assertEquals([['value' => 1, 'label' => null]], $result);
    }

    /**
     * Test exception handling in _toOptionArray (simulate getData throwing)
     * @throws ReflectionException
     */
    public function testToOptionArrayItemThrowsException(): void
    {
        $itemMock = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->getMock();
        $itemMock->method('getData')->willThrowException(new Exception('Data error'));

        $collectionMock = $this->getMockBuilder(CoreOptionCollection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $collectionMock->expects($this->any())
            ->method('getIterator')
            ->willReturn(new ArrayIterator([$itemMock]));

        $reflection = new ReflectionClass($this->plugin);
        $method = $reflection->getMethod('_toOptionArray');
        $method->setAccessible(true);

        $this->expectException(Exception::class);
        $method->invoke($this->plugin, $collectionMock, 'id', 'name', []);
    }
}
