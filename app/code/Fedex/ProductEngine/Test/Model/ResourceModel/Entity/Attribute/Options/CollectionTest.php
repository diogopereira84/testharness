<?php
declare(strict_types=1);

namespace Fedex\ProductEngine\Test\Model\ResourceModel\Entity\Attribute\Options;

use Fedex\Company\Api\Data\ConfigInterface;
use Fedex\ProductEngine\Model\ResourceModel\Entity\Attribute\Options\Collection;
use Magento\Framework\DB\Adapter\Pdo\Mysql;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Select\SelectRenderer;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use PHPUnit\Framework\TestCase;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\Collection as CoreOptionCollection;
use Magento\Framework\Data\Collection\EntityFactory;
use Psr\Log\LoggerInterface;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;

class CollectionTest extends TestCase
{
    /**
     * @var (\Magento\Framework\Data\Collection\EntityFactory & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $entityFactoryMock;
    /**
     * @var (\Magento\Framework\App\ResourceConnection & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $coreResourceMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    /**
     * @var (\Magento\Framework\Data\Collection\Db\FetchStrategyInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $fetchStrategyMock;
    /**
     * @var (\Magento\Framework\Event\ManagerInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $eventManagerMock;
    protected $storeManagerMock;
    protected $configInterface;
    protected $connectionMock;
    /**
     * @var (\Magento\Framework\DB\Select\SelectRenderer & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $selectRenderer;
    protected $select;
    protected $resourceMock;
    protected Collection $collectionMock;
    protected CoreOptionCollection $coreOptionCollectionMock;
    protected Option $optionMock;
    protected ToggleConfig $toggleConfigMock;
    protected function setUp(): void
    {
        $this->coreOptionCollectionMock = $this->createMock(CoreOptionCollection::class);
        $this->entityFactoryMock = $this->createMock(EntityFactory::class);
        $this->coreResourceMock = $this->createMock(ResourceConnection::class);
        $this->loggerMock = $this->getMockForAbstractClass(LoggerInterface::class);
        $this->fetchStrategyMock = $this->createMock(
            FetchStrategyInterface::class
        );
        $this->eventManagerMock = $this->getMockForAbstractClass(ManagerInterface::class);
        $this->storeManagerMock = $this->getMockForAbstractClass(StoreManagerInterface::class);
        $this->storeManagerMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->configInterface = $this->getMockForAbstractClass(ConfigInterface::class);
        $this->connectionMock = $this->createPartialMock(
            Mysql::class,
            ['select', 'describeTable', 'quoteIdentifier', '_connect', '_quote']  );
        $this->selectRenderer = $this->getMockBuilder(SelectRenderer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->select = new Select($this->connectionMock, $this->selectRenderer);

        $this->resourceMock = $this->getMockForAbstractClass(
            AbstractDb::class,
            [],
            '',
            false,
            true,
            true,
            ['__wakeup', 'getConnection', 'getMainTable', 'getTable']
        );

        $this->connectionMock->expects($this->any())->method('select')->willReturn($this->select);
        $this->connectionMock->expects($this->any())->method('quoteIdentifier')->willReturnArgument(0);
        $this->connectionMock->expects($this->any())
            ->method('describeTable')
            ->willReturnMap([
                [
                    'some_main_table',
                    null,
                    [
                        'col1' => [],
                        'col2' => [],
                    ],
                ],
                [
                    'some_extra_table',
                    null,
                    [
                        'col2' => [],
                        'col3' => [],
                    ]
                ],
                [
                    null,
                    null,
                    [
                        'col2' => [],
                        'col3' => [],
                        'col4' => [],
                    ]
                ],
            ]);
        $this->connectionMock->expects($this->any())->method('_quote')->willReturnArgument(0);
        $this->resourceMock->expects($this->any())->method('getConnection')->willReturn($this->connectionMock);
        $this->resourceMock->expects($this->any())->method('getMainTable')->willReturn('some_main_table');
        $this->resourceMock->expects($this->any())->method('getTable')->willReturn('some_extra_table');
        $this->optionMock = $this->createMock(Option::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->collectionMock = new Collection(
            $this->entityFactoryMock,
            $this->loggerMock,
            $this->fetchStrategyMock,
            $this->eventManagerMock,
            $this->coreResourceMock,
            $this->storeManagerMock,
            $this->configInterface,
            $this->toggleConfigMock,
            $this->connectionMock,
            $this->resourceMock
        );
    }

    /**
     * @return void
     */
    public function testToOptionArray() : void
    {
        $this->toggleConfigMock->method('getToggleConfigValue')
            ->with('xmen_remove_adobe_commerce_override')
            ->willReturn(false);
        $this->optionMock->expects($this->atMost(3))->method('getData')
            ->withConsecutive(['choice_id'], ['option_id'], ['value'])->willReturnOnConsecutiveCalls(1, 12, 'option label');

        $iterator = new \ArrayIterator([$this->optionMock]);
        $this->coreOptionCollectionMock->expects($this->any())->method('getIterator')->will($this->returnValue($iterator));

        $result = $this->collectionMock->toOptionArray($this->coreOptionCollectionMock, 'value');
        $this->assertEquals([], $result);
    }
}
