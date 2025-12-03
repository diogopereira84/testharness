<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Setup\Patch\Data;

use PHPUnit\Framework\TestCase;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Fedex\MarketplaceAdmin\Setup\Patch\Data\UpdateOrderStatus;

class UpdateOrderStatusTest extends TestCase
{
    /**
     * @var ModuleDataSetupInterface
     */
    private $moduleDataSetup;

    /**
     * @var StatusFactory
     */
    private $orderStatusFactory;

    /**
     * @var UpdateOrderStatus
     */
    private $updateOrderStatus;

    /**
     * Setup.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->moduleDataSetup = $this->createMock(ModuleDataSetupInterface::class);
        $this->orderStatusFactory = $this->createMock(StatusFactory::class);
        $this->updateOrderStatus = new UpdateOrderStatus($this->moduleDataSetup, $this->orderStatusFactory);
    }

    /**
     * Test apply method.
     *
     * @return void
     */
    public function testApply()
    {
        $orderStatus = $this->getMockBuilder(\Magento\Sales\Model\Order\Status::class)
            ->setMethods(['setLabel','load','getId','save'])
            ->disableOriginalConstructor()
            ->getMock();

        $orderStatus->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(['Assigned', 'label'], ['In Progress', 'label'])
            ->willReturnOnConsecutiveCalls($orderStatus, $orderStatus);

        $orderStatus->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(true);

        $orderStatus->expects($this->exactly(2))
            ->method('setLabel')
            ->withConsecutive(['Ordered'], ['Processing']);

        $orderStatus->expects($this->exactly(2))
            ->method('save');

        $this->orderStatusFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($orderStatus);

        $this->moduleDataSetup->expects($this->once())
            ->method('startSetup');

        $this->moduleDataSetup->expects($this->once())
            ->method('endSetup');

        $this->updateOrderStatus->apply();
    }

    /**
     * Test revert method.
     *
     * @return void
     */
    public function testRevert()
    {
        $orderStatus = $this->getMockBuilder(\Magento\Sales\Model\Order\Status::class)
            ->setMethods(['setLabel','load','getId','save'])
            ->disableOriginalConstructor()
            ->getMock();
        $orderStatus->expects($this->exactly(2))
            ->method('load')
            ->withConsecutive(['Ordered', 'label'], ['Processing', 'label'])
            ->willReturnOnConsecutiveCalls($orderStatus, $orderStatus);

        $orderStatus->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(true);

        $orderStatus->expects($this->exactly(2))
            ->method('setLabel')
            ->withConsecutive(['Assigned'], ['In Progress']);

        $orderStatus->expects($this->exactly(2))
            ->method('save');

        $this->orderStatusFactory->expects($this->exactly(2))
            ->method('create')
            ->willReturn($orderStatus);

        $this->moduleDataSetup->expects($this->once())
            ->method('startSetup');

        $this->moduleDataSetup->expects($this->once())
            ->method('endSetup');

        $this->updateOrderStatus->revert();
    }

    /**
     * Test getDependencies method.
     *
     * @return void
     */
    public function testGetDependencies()
    {
        $dependencies = $this->updateOrderStatus->getDependencies();

        $this->assertIsArray($dependencies);
        $this->assertEmpty($dependencies);
    }

    /**
     * Test getAliases method.
     *
     * @return void
     */
    public function testGetAliases()
    {
        $aliases = $this->updateOrderStatus->getAliases();

        $this->assertIsArray($aliases);
        $this->assertEmpty($aliases);
    }
}
