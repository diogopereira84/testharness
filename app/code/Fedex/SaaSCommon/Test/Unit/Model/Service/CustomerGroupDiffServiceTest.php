<?php

namespace Fedex\SaaSCommon\Test\Unit\Model\Service;

use Fedex\SaaSCommon\Model\Service\CustomerGroupDiffService;
use Magento\Eav\Api\AttributeOptionManagementInterface;
use Magento\Eav\Api\Data\AttributeOptionInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\InputException;
use PHPUnit\Framework\TestCase;

class CustomerGroupDiffServiceTest extends TestCase
{
    private $attributeOptionManagement;
    private $service;

    protected function setUp(): void
    {
        $this->attributeOptionManagement = $this->createMock(AttributeOptionManagementInterface::class);
        $this->service = new CustomerGroupDiffService($this->attributeOptionManagement);
    }

    public function testGetAllowedCustomerGroupsOptionsReturnsOptions()
    {
        $option = $this->createMock(AttributeOptionInterface::class);
        $this->attributeOptionManagement->expects($this->once())
            ->method('getItems')
            ->with(Product::ENTITY, CustomerGroupDiffService::ATTRIBUTE_CODE)
            ->willReturn([$option]);

        $result = $this->service->getAllowedCustomerGroupsOptions();
        $this->assertSame([$option], $result);
    }

    public function testFindMissingCustomerGroupOptionsReturnsMissing()
    {
        $option1 = $this->createMock(AttributeOptionInterface::class);
        $option1->method('getValue')->willReturn('A');
        $option2 = $this->createMock(AttributeOptionInterface::class);
        $option2->method('getValue')->willReturn('B');

        $this->attributeOptionManagement->expects($this->once())
            ->method('getItems')
            ->willReturn([$option1, $option2]);

        $result = $this->service->findMissingCustomerGroupOptions(['A', 'B', 'C']);
        $this->assertSame(['C'], $result);
    }

    public function testAllowedCustomerGroupsAttributeOptionLengthReturnsCount()
    {
        $option1 = $this->createMock(AttributeOptionInterface::class);
        $option2 = $this->createMock(AttributeOptionInterface::class);

        $this->attributeOptionManagement->expects($this->once())
            ->method('getItems')
            ->willReturn([$option1, $option2]);

        $result = $this->service->allowedCustomerGroupsAttributeOptionLength();
        $this->assertSame(2, $result);
    }

    public function testAllowedCustomerGroupsAttributeOptionLengthReturnsZeroForNonArray()
    {
        $this->attributeOptionManagement->expects($this->once())
            ->method('getItems')
            ->willReturn(null);

        $result = $this->service->allowedCustomerGroupsAttributeOptionLength();
        $this->assertSame(0, $result);
    }

    public function testConvertToLabelValueMapReturnsMap()
    {
        $option1 = $this->createMock(AttributeOptionInterface::class);
        $option1->method('getValue')->willReturn('X');
        $option2 = $this->createMock(AttributeOptionInterface::class);
        $option2->method('getValue')->willReturn('Y');

        $result = $this->service->convertToLabelValueMap([$option1, $option2]);
        $this->assertSame(['X' => 'X', 'Y' => 'Y'], $result);
    }

    public function testConvertToLabelValueMapThrowsOnInvalidOption()
    {
        $this->expectException(InputException::class);
        $this->service->convertToLabelValueMap([new \stdClass()]);
    }
}

