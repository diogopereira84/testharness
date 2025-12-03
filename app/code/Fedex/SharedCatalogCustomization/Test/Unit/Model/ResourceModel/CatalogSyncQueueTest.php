<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare (strict_types = 1);

namespace Fedex\SharedCatalogCustomization\Test\Unit\Model\ResourceModel;

use Fedex\SharedCatalogCustomization\Model\ResourceModel\CatalogSyncQueue;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

class CatalogSyncQueueTest extends TestCase
{
    protected $connection;
    protected $selectRenderer;
    protected $_resource;
    protected $_resourceModel;
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->connection = $this->getMockForAbstractClass(
            Mysql::class,
            [],
            '',
            false,
            true,
            true,
            ['getTransactionLevel', 'fetchCol', 'select', 'prepareSqlCondition', '_connect']
        );
        $this->selectRenderer = $this->getMockBuilder(SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $select = new Select($this->connection, $this->selectRenderer);

        $this->connection->expects($this->once())->method('select')->willReturn($select);

        $this->_resource = $this->createMock(ResourceConnection::class);
        $this->_resource->expects($this->any())->method('getTableName')->willReturnArgument(0);
        $this->_resource->expects(
            $this->any()
        )->method(
            'getConnection'
        )->willReturn(
            $this->connection
        );

        $contextMock = $this->createMock(Context::class);
        $contextMock->expects($this->once())->method('getResources')->willReturn($this->_resource);

        $this->_resourceModel = new CatalogSyncQueue($contextMock, );
    }

    /**
     * @test getEmailSentIds
     * *
     * @return void
     */
    public function testgetEmailSentIds()
    {
        $this->assertEquals(null, $this->_resourceModel->getEmailSentIds());
    }

}
