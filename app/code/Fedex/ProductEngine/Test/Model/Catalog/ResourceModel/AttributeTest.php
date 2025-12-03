<?php
/**
 * @category Fedex
 * @package Fedex_ProductEngine
 * @copyright Fedex (c) 2021.
 * @author Iago Lima <ilima@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Model\Catalog\ResourceModel;

use Fedex\ProductEngine\Model\Catalog\ResourceModel\Attribute;
use Magento\Catalog\Model\Attribute\LockValidatorInterface;
use Magento\Catalog\Model\ResourceModel\Attribute\RemoveProductAttributeData;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Type;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AttributeTest extends TestCase
{
    protected Attribute $attributeMock;
    protected Context|MockObject $contextMock;
    protected StoreManagerInterface|MockObject $storeManagerMock;
    protected Type|MockObject $eavEntityTypeMock;
    protected Config|MockObject $eavConfigMock;
    protected LockValidatorInterface|MockObject $lockValidatorMock;
    protected RemoveProductAttributeData|MockObject $removeProductAttributeDataMock;
    protected AdapterInterface|MockObject $adapterInterfaceMock;
    protected AbstractAttribute|MockObject $abstractAttributeMock;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManagerMock = $this->createMock(StoreManagerInterface::class);
        $this->eavEntityTypeMock = $this->createMock(Type::class);
        $this->eavConfigMock = $this->createMock(Config::class);
        $this->lockValidatorMock = $this->createMock(LockValidatorInterface::class);
        $this->removeProductAttributeDataMock = $this->createMock(RemoveProductAttributeData::class);
        $this->abstractAttributeMock = $this->createMock(AbstractAttribute::class);

        $this->adapterInterfaceMock = $this->getMockBuilder(AdapterInterface::class)
            ->addMethods(['lastInsertId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->attributeMock = $this->getMockBuilder(Attribute::class)
            ->onlyMethods(['getConnection', 'getTable'])
            ->disableOriginalConstructor()
            ->setConstructorArgs(
                [
                    $this->contextMock,
                    $this->storeManagerMock,
                    $this->eavEntityTypeMock,
                    $this->eavConfigMock,
                    $this->lockValidatorMock,
                    null,
                    $this->removeProductAttributeDataMock
                ])
            ->getMockForAbstractClass();
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateAttributeOption(): void
    {
        $intOptionId = 1;
        $option = ['value' => [$intOptionId => 'label'], 'order' => [$intOptionId => '0'], 'choice_id' => [$intOptionId => $intOptionId]];
        $data = ['sort_order' => 0, 'choice_id' => $intOptionId];
        $where = ['option_id = ?' => $intOptionId];
        $this->adapterInterfaceMock->expects($this->once())->method('update')->with('catalog_eav_attribute_option', $data, $where)->willReturn(1);
        $this->attributeMock->expects($this->once())->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->attributeMock->expects($this->once())->method('getTable')->with('eav_attribute_option')->willReturn('catalog_eav_attribute_option');

        $reflectionMethod = new \ReflectionMethod($this->attributeMock, '_updateAttributeOption');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->attributeMock, $this->abstractAttributeMock, 1, $option);
        $this->assertEquals(1, $actual);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateAttributeOptionOptionIdNotSavedYet(): void
    {
        $intOptionId = 'option_100';
        $intOptionIdAfterInsert = '100';
        $attributeId = 150;
        $tableName = 'eav_attribute_option';
        $option = ['value' => [$intOptionId => 'label'], 'order' => [$intOptionId => '0'], 'choice_id' => [$intOptionId => 0]];
        $data = ['attribute_id' => $attributeId, 'sort_order' => 0, 'choice_id' => 0];
        $this->adapterInterfaceMock->expects($this->once())->method('insert')->with($tableName, $data)->willReturn(1);
        $this->adapterInterfaceMock->expects($this->once())->method('lastInsertId')->with($tableName)->willReturn($intOptionIdAfterInsert);
        $this->attributeMock->expects($this->once())->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->attributeMock->expects($this->once())->method('getTable')->with($tableName)->willReturn($tableName);
        $this->abstractAttributeMock->expects($this->once())->method('getId')->willReturn($attributeId);

        $reflectionMethod = new \ReflectionMethod($this->attributeMock, '_updateAttributeOption');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->attributeMock, $this->abstractAttributeMock, $intOptionId, $option);
        $this->assertEquals($intOptionIdAfterInsert, $actual);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateAttributeOptionDelete(): void
    {
        $attributeId = 150;
        $intOptionId = 1;
        $varcharTable = 'catalog_product_entity_varchar';
        $option = ['value' => [$intOptionId => 'label'], 'delete' => [$intOptionId => true], 'order' => [$intOptionId => '0'], 'choice_id' => [$intOptionId => $intOptionId]];
        $concatSql = new \Zend_Db_Expr(sprintf('CONCAT(%s)', implode(', ', ["','", 'value', "','"])));
        $update['value'] = new \Zend_Db_Expr("TRIM(BOTH ',' FROM REPLACE(CONCAT(',', value, ','),',1,',','))");
        $where = "attribute_id = '150' AND FIND_IN_SET(1, value)";
        $this->adapterInterfaceMock->expects($this->once())->method('update')
            ->with($varcharTable, $update, $where)->willReturn(1);

        $this->adapterInterfaceMock->expects($this->atMost(2))->method('quoteInto')
            ->withConsecutive(['attribute_id = ?', $attributeId], ["TRIM(BOTH ',' FROM REPLACE($concatSql,',?,',','))", $intOptionId])
            ->willReturnOnConsecutiveCalls("attribute_id = '$attributeId'", "TRIM(BOTH ',' FROM REPLACE(CONCAT(',', value, ','),',1,',','))");

        $this->adapterInterfaceMock->expects($this->once())->method('prepareSqlCondition')
            ->with('value', ['finset' => $intOptionId])->willReturn("FIND_IN_SET(1, value)");

        $this->adapterInterfaceMock->expects($this->once())->method('getConcatSql')
            ->with(["','", 'value', "','"])->willReturn($concatSql);

        $this->attributeMock->expects($this->atMost(2))->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->attributeMock->expects($this->once())->method('getTable')->with('eav_attribute_option')->willReturn('catalog_eav_attribute_option');

        $this->abstractAttributeMock->expects($this->once())->method('getBackendTable')->willReturn($varcharTable);
        $this->abstractAttributeMock->expects($this->once())->method('getBackendType')->willReturn('varchar');
        $this->abstractAttributeMock->expects($this->once())->method('getAttributeId')->willReturn($attributeId);

        $reflectionMethod = new \ReflectionMethod($this->attributeMock, '_updateAttributeOption');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->attributeMock, $this->abstractAttributeMock, 1, $option);
        $this->assertFalse($actual);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateAttributeOptionDeleteEmpty(): void
    {
        $attributeId = false;
        $intOptionId = 1;
        $option = ['value' => [$intOptionId => 'label'], 'delete' => [$intOptionId => true], 'order' => [$intOptionId => '0'], 'choice_id' => [$intOptionId => $intOptionId]];
        $varcharTable = '';
        $where = "attribute_id = '150' AND FIND_IN_SET(1, value)";

        $this->attributeMock->expects($this->once())->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->attributeMock->expects($this->once())->method('getTable')->with('eav_attribute_option')->willReturn('catalog_eav_attribute_option');

        $this->abstractAttributeMock->expects($this->once())->method('getBackendTable')->willReturn($varcharTable);
        $this->abstractAttributeMock->expects($this->once())->method('getAttributeId')->willReturn($attributeId);

        $reflectionMethod = new \ReflectionMethod($this->attributeMock, '_updateAttributeOption');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->attributeMock, $this->abstractAttributeMock, 1, $option);
        $this->assertFalse($actual);
    }

    /**
     * @return void
     * @throws \ReflectionException
     */
    public function testUpdateAttributeOptionDeleteNotVarchar(): void
    {
        $attributeId = 150;
        $intOptionId = 1;
        $varcharTable = 'catalog_product_entity_text';
        $option = ['value' => [$intOptionId => 'label'], 'delete' => [$intOptionId => true], 'order' => [$intOptionId => '0'], 'choice_id' => [$intOptionId => $intOptionId]];
        $update['value'] = null;
        $where = "attribute_id = '150' AND value = 1";
        $this->adapterInterfaceMock->expects($this->once())->method('update')
            ->with($varcharTable, $update, $where)->willReturn(1);

        $this->adapterInterfaceMock->expects($this->atMost(2))->method('quoteInto')
            ->withConsecutive(['attribute_id = ?', $attributeId], [' AND value = ?', $intOptionId])
            ->willReturnOnConsecutiveCalls("attribute_id = '$attributeId'", " AND value = $intOptionId");

        $this->attributeMock->expects($this->atMost(2))->method('getConnection')->willReturn($this->adapterInterfaceMock);
        $this->attributeMock->expects($this->once())->method('getTable')->with('eav_attribute_option')->willReturn('catalog_eav_attribute_option');

        $this->abstractAttributeMock->expects($this->once())->method('getBackendTable')->willReturn($varcharTable);
        $this->abstractAttributeMock->expects($this->once())->method('getBackendType')->willReturn('text');
        $this->abstractAttributeMock->expects($this->once())->method('getAttributeId')->willReturn($attributeId);

        $reflectionMethod = new \ReflectionMethod($this->attributeMock, '_updateAttributeOption');
        $reflectionMethod->setAccessible(true);
        $actual = $reflectionMethod->invoke($this->attributeMock, $this->abstractAttributeMock, 1, $option);
        $this->assertFalse($actual);
    }
}
