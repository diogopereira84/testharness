<?php

namespace Fedex\SubmitOrderSidebar\Test\Unit\Model\Data\BillingField;

use PHPUnit\Framework\TestCase;
use Fedex\SubmitOrderSidebar\Model\Data\BillingField\Collection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;

class CollectionTest extends TestCase
{
    private $entityFactory;
    private $toggleConfig;
    private $collection;

    protected function setUp(): void
    {
        $this->entityFactory = $this->createMock(EntityFactoryInterface::class);
        $this->toggleConfig = $this->createMock(ToggleConfig::class);
        $this->collection = new Collection($this->entityFactory, $this->toggleConfig);
    }

    public function testGetPoNumber()
    {
        $poNumber = $this->getMockBuilder(DataObject::class)
            ->disableOriginalConstructor()
            ->addMethods(['getValue'])
            ->getMock();
        $poNumber->method('getValue')->willReturn('12345');
        $this->collection->addItem($poNumber);

        $this->assertEquals('12345', $this->collection->getPoNumer());
    }

    public function testHasPoNumber()
    {
        $this->assertFalse($this->collection->hasPoNumber());

        $this->collection->clear();
        $poNumber = $this->createMock(DataObject::class);
        $this->collection->addItem($poNumber);
        $this->collection->load();

        $this->assertTrue($this->collection->hasPoNumber());
    }

    public function testRemovePoReferenceId()
    {
        $poNumber = $this->createMock(DataObject::class);
        $this->collection->addItem($poNumber);

        $this->assertTrue($this->collection->hasPoNumber());

        $this->collection->clear();
        $this->collection->removePoReferenceId();

        $this->assertFalse($this->collection->hasPoNumber());
    }

    public function testToArrayApi()
    {
        $billingField = new DataObject(['first_field' => 'value1', 'second_field' => 'value2']);
        $this->collection->addItem($billingField);

        $this->toggleConfig->method('getToggleConfigValue')->willReturn(true);

        $expected = [
            ['second_field' => 'value2']
        ];

        $this->assertEquals($expected, $this->collection->toArrayApi());
    }

    public function testToArrayApiWithoutToggle()
    {
        $billingField = new DataObject(['first_field' => 'value1', 'second_field' => 'value2']);
        $this->collection->addItem($billingField);

        $this->toggleConfig->method('getToggleConfigValue')->willReturn(false);

        $expected = [
            ['first_field' => 'value1', 'second_field' => 'value2']
        ];

        $this->assertEquals($expected, $this->collection->toArrayApi());
    }
}
