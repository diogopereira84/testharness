<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceWebhook
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Tiago Daniel <tiago.daniel.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceAdmin\Test\Unit\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceAdmin\Setup\Patch\Data\UpdateOrderStatusLabels;

class UpdateOrderStatusLabelsTest extends TestCase
{
    /**
     * @var UpdateOrderStatusLabels
     */
    private UpdateOrderStatusLabels $patch;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $statusCollectionFactory;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->statusCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->patch = new UpdateOrderStatusLabels($this->statusCollectionFactory);
    }

    /**
     * Test apply method.
     *
     * @return void
     */
    public function testApply(): void
    {
        $statusCollection = $this->createMock(\Magento\Sales\Model\ResourceModel\Order\Status\Collection::class);
        $confirmedStatus = $this->getMockBuilder(\Magento\Sales\Model\Order\Status::class)
            ->setMethods(['setLabel','save'])
            ->disableOriginalConstructor()
            ->getMock();
        $newStatus = $this->getMockBuilder(\Magento\Sales\Model\Order\Status::class)
            ->setMethods(['setLabel','save'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->statusCollectionFactory->expects($this->once())
            ->method('create')
            ->willReturn($statusCollection);

        $statusCollection->expects($this->exactly(2))
            ->method('getItemByColumnValue')
            ->withConsecutive(['status', 'confirmed'], ['status', 'new'])
            ->willReturnOnConsecutiveCalls($confirmedStatus, $newStatus);

        $confirmedStatus->expects($this->once())
            ->method('setLabel')
            ->with('Processing')
            ->willReturnSelf();

        $confirmedStatus->expects($this->once())
            ->method('save');

        $newStatus->expects($this->once())
            ->method('setLabel')
            ->with('Ordered')
            ->willReturnSelf();

        $newStatus->expects($this->once())
            ->method('save');

        $this->patch->apply();
    }

    public function testGetDependencies(): void
    {
        $this->assertSame([], $this->patch->getDependencies());
    }

    public function testGetAliases(): void
    {
        $this->assertSame([], $this->patch->getAliases());
    }
}
