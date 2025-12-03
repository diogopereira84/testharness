<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\CIDPSG\Test\Unit\Setup\Patch\Data;

use Fedex\CIDPSG\Setup\Patch\Data\InsertDefaultPsgCustomerFields;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class InsertDefaultPsgCustomerFieldsTest extends TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    /**
     * @var ModuleDataSetupInterface $moduleDataSetupMock
     */
    protected $moduleDataSetupMock;

    /**
     * @var InsertDefaultPsgCustomerFields $InsertDefaultPsgCustomerFields
     */
    protected $InsertDefaultPsgCustomerFields;

   /**
    * Setup method
    */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection', 'getTableName', 'insert', 'insertMultiple', 'lastInsertId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->InsertDefaultPsgCustomerFields = $this->objectManager->getObject(
            InsertDefaultPsgCustomerFields::class,
            [
                'moduleDataSetup' => $this->moduleDataSetupMock
            ]
        );
    }

    /**
     * Test Apply
     *
     * @return void
     */
    public function testApply()
    {
        $this->moduleDataSetupMock->expects($this->any())->method('getConnection')->willReturnSelf();
        $this->moduleDataSetupMock->expects($this->any())->method('getTableName')->willReturn(1);
        $this->moduleDataSetupMock->expects($this->once())->method('insert')->willReturn(1);
        $this->moduleDataSetupMock->expects($this->once())->method('lastInsertId')->willReturn(1);
        $this->moduleDataSetupMock->expects($this->once())->method('insertMultiple')->willReturn(1);
        $this->moduleDataSetupMock
            ->method('getTable')
            ->withConsecutive(
                ['psg_customer'],
                ['psg_customer_fields']
            )
            ->willReturnOnConsecutiveCalls(
                'psg_customer',
                'psg_customer_fields'
            );
        $this->moduleDataSetupMock->expects($this->any())->method('endSetup')->willReturnSelf();

        $this->assertEquals(null, $this->InsertDefaultPsgCustomerFields->apply());
    }

    /**
     * Test getDependencies
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertIsArray($this->InsertDefaultPsgCustomerFields->getDependencies());
    }

    /**
     * Test getAliases
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertIsArray($this->InsertDefaultPsgCustomerFields->getAliases());
    }

    /**
     * Test getVersion
     *
     * @return void
     */
    public function testGetVersion()
    {
        $this->assertEquals('1.0.3', $this->InsertDefaultPsgCustomerFields->getVersion());
    }
}
