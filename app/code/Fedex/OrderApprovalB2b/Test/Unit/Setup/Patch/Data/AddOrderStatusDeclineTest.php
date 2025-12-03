<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Setup\Patch\Data;

use Fedex\OrderApprovalB2b\Setup\Patch\Data\AddOrderStatusDecline;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class AddOrderStatusDeclineTest extends TestCase
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
     * @var AddOrderStatusDecline $addOrderStatusDecline
     */
    protected $addOrderStatusDecline;

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

        $this->addOrderStatusDecline = $this->objectManager->getObject(
            AddOrderStatusDecline::class,
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

        $this->assertEquals(null, $this->addOrderStatusDecline->apply());
    }

    /**
     * Test getDependencies
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $this->assertIsArray($this->addOrderStatusDecline->getDependencies());
    }

    /**
     * Test getAliases
     *
     * @return void
     */
    public function testGetAliases()
    {
        $this->assertIsArray($this->addOrderStatusDecline->getAliases());
    }
}
