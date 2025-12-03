<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Test\Unit\Model\ResourceModel\EmailVerificationCustomer;

use Fedex\EmailVerification\Model\EmailVerificationCustomer;
use Fedex\EmailVerification\Model\ResourceModel\EmailVerificationCustomer as EmailVerificationCustomerResourceModel;
use Fedex\EmailVerification\Model\ResourceModel\EmailVerificationCustomer\Collection;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Flag\FlagResource;
use Psr\Log\LoggerInterface;

class CollectionTest extends TestCase
{
    /**
     * @var Collection
     */
    protected Collection $collectionMock;

    /**
     * Test construct method
     *
     * @return void
     */
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

        $this->collectionMock = new Collection(
            $entityFactoryMock,
            $loggerMock,
            $fetchStrategyMock,
            $managerMock,
            $connectionMock,
            $resourceMock
        );

        $this->assertInstanceOf(Collection::class, $this->collectionMock);
        $this->assertEquals(EmailVerificationCustomer::class, $this->collectionMock->getModelName());
        $this->assertEquals(
            EmailVerificationCustomerResourceModel::class,
            $this->collectionMock->getResourceModelName()
        );
    }
}
