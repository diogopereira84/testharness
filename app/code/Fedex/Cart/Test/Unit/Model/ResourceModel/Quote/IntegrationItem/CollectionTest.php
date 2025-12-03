<?php
/**
 * @category     Fedex
 * @package      Fedex_Cart
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Eduardo Diogo Dias <edias@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\ResourceModel\Quote\IntegrationItem;

use Fedex\Cart\Model\Quote\IntegrationItem as IntegrationItemModel;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem as IntegrationItemResourceModel;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationItem\Collection;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Flag\FlagResource;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected Collection $model;

    public function testConstruct()
    {
        $entityFactoryMock = $this->getMockForAbstractClass(EntityFactoryInterface::class);
        $loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $fetchStrategyMock = $this->getMockForAbstractClass(FetchStrategyInterface::class);
        $managerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $connectionMock = $this->createMock(Mysql::class);
        $selectRendererMock = $this->createMock(SelectRenderer::class);
        $resourceMock = $this->createMock(FlagResource::class);
        $resourceMock->expects($this->any())->method('getConnection')->willReturn($connectionMock);
        $selectMock = $this->getMockBuilder(Select::class)
            ->onlyMethods(['getPart', 'setPart', 'from', 'columns'])
            ->setConstructorArgs([$connectionMock, $selectRendererMock])
            ->getMock();
        $connectionMock->expects($this->any())->method('select')->willReturn($selectMock);

        $this->model = new Collection(
            $entityFactoryMock,
            $loggerMock,
            $fetchStrategyMock,
            $managerMock,
            $connectionMock,
            $resourceMock
        );

        $this->assertInstanceOf(Collection::class, $this->model);
        $this->assertEquals(IntegrationItemModel::class, $this->model->getModelName());
        $this->assertEquals(IntegrationItemResourceModel::class, $this->model->getResourceModelName());
    }
}
