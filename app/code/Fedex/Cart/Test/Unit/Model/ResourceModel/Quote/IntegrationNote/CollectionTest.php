<?php
/**
 * @category    Fedex
 * @package     Fedex_Cart
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\Cart\Test\Unit\Model\ResourceModel\Quote\IntegrationNote;

use Fedex\Cart\Model\Quote\IntegrationNote as IntegrationNoteModel;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote as IntegrationNoteResourceModel;
use Fedex\Cart\Model\ResourceModel\Quote\IntegrationNote\Collection;
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
        $this->assertEquals(IntegrationNoteModel::class, $this->model->getModelName());
        $this->assertEquals(IntegrationNoteResourceModel::class, $this->model->getResourceModelName());
    }
}
