<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Setup\Patch\Data;

use Fedex\OrderApprovalB2b\Setup\Patch\Data\AddOrderStatusPendingApproval;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AddOrderStatusPendingApprovalTest extends TestCase
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
     * @var AddOrderStatusPendingApproval $addOrderStatusPendingApproval
     */
    protected $addOrderStatusPendingApproval;

   /**
    * Setup method
    */
    protected function setUp(): void
    {
        $this->moduleDataSetupMock = $this->getMockBuilder(ModuleDataSetupInterface::class)
            ->setMethods(['getConnection', 'getTableName', 'insertArray'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);

        $this->addOrderStatusPendingApproval = $this->objectManager->getObject(
            AddOrderStatusPendingApproval::class,
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
        $this->moduleDataSetupMock->expects($this->any())->method('insertArray')->willReturn(1);
        $this->moduleDataSetupMock
            ->method('getTable')
            ->withConsecutive(
                ['sales_order_status'],
                ['sales_order_status_state']
            )
            ->willReturnOnConsecutiveCalls(
                'sales_order_status',
                'sales_order_status_state'
            );
        $this->moduleDataSetupMock->expects($this->any())->method('endSetup')->willReturnSelf();

        $this->assertEquals(null, $this->addOrderStatusPendingApproval->apply());
    }

    /**
     * Test getDependencies
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertIsArray($this->addOrderStatusPendingApproval->getDependencies());
    }

    /**
     * Test getAliases
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertIsArray($this->addOrderStatusPendingApproval->getAliases());
    }
}
