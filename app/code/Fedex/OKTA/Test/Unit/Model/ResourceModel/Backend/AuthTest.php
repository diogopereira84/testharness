<?php
/**
 * @category    Fedex
 * @package     Fedex_OKTA
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jonatan.santos.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\OKTA\Test\Unit\Model\ResourceModel\Backend;

use Magento\Framework\DB\Select;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\Framework\Model\ResourceModel\Db\TransactionManager;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Fedex\OKTA\Model\ResourceModel\Backend\Auth;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    /**
     * @var Auth
     */
    private Auth $auth;

    /**
     * @var Context|MockObject
     */
    private Context $contextMock;

    /**
     * @var Select|MockObject
     */
    private Select $selectMock;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface $adapterMock;

    /**
     * @var TransactionManager|MockObject
     */
    private TransactionManager $transactionManagerMock;

    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection $resourceConnectionMock;

    /**
     * @var ObjectRelationProcessor|MockObject
     */
    private ObjectRelationProcessor $objectRelationProcessorMock;

    protected function setUp(): void
    {
        $this->selectMock = $this->createMock(Select::class);
        $this->adapterMock = $this->getMockForAbstractClass(AdapterInterface::class);
        $this->adapterMock->method('select')->willReturn($this->selectMock);
        $this->selectMock->method('from')->willReturn($this->selectMock);
        $this->selectMock->method('where')->willReturn($this->selectMock);
        $this->contextMock = $this->createMock(Context::class);
        $this->transactionManagerMock = $this->createMock(TransactionManager::class);
        $this->resourceConnectionMock = $this->createMock(ResourceConnection::class);
        $this->objectRelationProcessorMock = $this->createMock(ObjectRelationProcessor::class);
        $this->resourceConnectionMock->method('getConnection')->willReturn($this->adapterMock);
        $this->contextMock->expects($this->once())
            ->method('getTransactionManager')
            ->willReturn($this->transactionManagerMock);
        $this->contextMock->expects($this->once())
            ->method('getResources')
            ->willReturn($this->resourceConnectionMock);
        $this->contextMock->expects($this->once())
            ->method('getObjectRelationProcessor')
            ->willReturn($this->objectRelationProcessorMock);
        $this->auth = new Auth($this->contextMock);
    }

    public function testGetRelationshipId(): void
    {
        $this->adapterMock->method('fetchOne')->willReturn(2);
        $this->auth->getRelationshipId('123');
    }

    public function testAddRelationship(): void
    {
        $this->adapterMock->expects($this->once())->method('insertOnDuplicate')->willReturn(1);
        $this->assertEquals(1, $this->auth->addRelationship('123', 345));
    }

    public function testGetRelationshipData(): void
    {
        $this->adapterMock->method('fetchOne')->willReturn('123');
        $this->auth->getRelationshipData(345);
    }
}
