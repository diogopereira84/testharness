<?php
namespace Fedex\OptimizeProductinstance\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Fedex\OptimizeProductinstance\Model\OrderCompressionFactory;
use Fedex\OptimizeProductinstance\Model\OrderCompression;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Fedex\OptimizeProductinstance\Model\ResourceModel\OrderCompression\CollectionFactory as OrderCompressionCollectionFactory;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ResourceConnection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OptimizeProductinstance\Cron\OrderIdSaveQueueProcessCron;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class OrderIdSaveQueueProcessCronTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $order;
    protected $orderCompression;
    protected $orderCompressionCollection;
    protected $resourceConnection;
    protected $selectMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $orderIdSaveQueueProcessCron;
    public const EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS = "explorers_optimize_quotes_and_orders_within_14_months";

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var OrderCompressionFactory
     */
    protected $orderCompressionFactory;

    /**
     * @var OrderCollectionFactory
     */
    protected $orderCollectionFactory;

    /**
     * @var OrderCompressionCollectionFactory
     */
    protected $orderCompressionCollectionFactory;

    /**
     * @var OptimizeItemInstanceHelper
     */
    protected $optimizeItemInstanceHelper;

    /**
     * @var ResourceConnection
     */
    protected $resource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->orderFactory = $this->getMockBuilder(OrderFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->order = $this->getMockBuilder(Order::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCollection','addFieldToSelect','getSelect','addFieldToFilter'])
        ->getMock();
        $this->orderCompressionFactory = $this->getMockBuilder(OrderCompressionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->orderCompression = $this->getMockBuilder(OrderCompression::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCollection','save', 'getId'])
        ->getMock();
        $this->orderCompressionCollection = $this->getMockBuilder(AbstractCollection::class)
        ->disableOriginalConstructor()
        ->setMethods(['addFieldToSelect','getSelect','addFieldToFilter'])
        ->getMock();
        $this->orderCollectionFactory = $this->getMockBuilder(OrderCollectionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->orderCompressionCollectionFactory = $this->getMockBuilder(OrderCompressionCollectionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->optimizeItemInstanceHelper = $this->getMockBuilder(OptimizeItemInstanceHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['pushTempOrderCompressionIdQueue'])
        ->getMock();
        $this->resourceConnection = $this->getMockBuilder(ResourceConnection::class)
        ->disableOriginalConstructor()
        ->setMethods(['getTableName'])
        ->getMock();
        $this->selectMock = $this->getMockBuilder(Select::class)
        ->setMethods(['join', 'joinLeft', 'where', 'columns', 'limit','__toString'])
        ->disableOriginalConstructor()
        ->getMock();
        $this->toggleConfig = $this->getMockBuilder(ToggleConfig::class)
        ->disableOriginalConstructor()
        ->setMethods(['getToggleConfigValue'])
        ->getMock();
        $this->loggerMock  = $this->getMockBuilder(LoggerInterface::class)
        ->setMethods(['error', 'info'])
        ->disableOriginalConstructor()
        ->getMockForAbstractClass();
        $this->orderIdSaveQueueProcessCron    = $this->objectManager->getObject(
            OrderIdSaveQueueProcessCron::class,
            [
                'orderFactory'                         => $this->orderFactory,
                'orderCompressionFactory'              => $this->orderCompressionFactory,
                'orderCollectionFactory'               => $this->orderCollectionFactory,
                'orderCompressionCollectionFactory'    => $this->orderCompressionCollectionFactory,
                'optimizeItemInstanceHelper'           => $this->optimizeItemInstanceHelper,
                'resourceConnection'                   => $this->resourceConnection,
                'toggleConfig'                         => $this->toggleConfig,
                'logger'                           => $this->loggerMock
            ]
        );
    }

    /**
     * Test execute.
     *
     * @return bool
     */
    public function testExecute()
    {
        $this->toggleConfig->expects($this->any())
        ->method('getToggleConfigValue')
        ->with(self::EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS)
        ->willReturn(true);
        $this->orderFactory->expects($this->any())
        ->method('create')->willReturn($this->order);
        $this->order->expects($this->any())
        ->method('getCollection')->willReturnSelf();
        $this->orderCompressionFactory->expects($this->any())
        ->method('create')->willReturn($this->orderCompression);
        $this->orderCompression->expects($this->any())
        ->method('getCollection')->willReturn($this->orderCompressionCollection);
        $this->orderCompressionCollection->expects($this->any())
        ->method('addFieldToSelect')->willReturnSelf();
        $this->orderCompressionCollection->expects($this->any())
        ->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
        ->method('__toString')->willReturn('Test String');
        $this->resourceConnection->expects($this->any())
        ->method('getTableName')->willReturnSelf();
        $this->order->expects($this->any())
        ->method('addFieldToSelect')->willReturnSelf();
        $this->order->expects($this->any())
        ->method('addFieldToFilter')->willReturnSelf();
        $this->order->expects($this->any())
        ->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
        ->method('joinLeft')->willReturnSelf();
        $this->selectMock->expects($this->any())
        ->method('where')->willReturnSelf();
        $this->selectMock->expects($this->any())
        ->method('limit')->willReturnSelf();
        $this->assertEquals(true, $this->orderIdSaveQueueProcessCron->execute());
    }

    /**
     * Test addOrderDataInTempTable.
     *
     * @return void
     */
    public function testAddOrderDataInTempTable()
    {
        $this->orderFactory->expects($this->any())
        ->method('create')->willReturn($this->order);
        $this->order->expects($this->any())
        ->method('getCollection')->willReturn($this);
        $this->orderCompressionFactory->expects($this->any())
        ->method('create')->willReturn($this->orderCompression);
        $this->orderCompression->expects($this->any())
        ->method('save')->willReturnSelf();
        $this->orderCompression->expects($this->any())
        ->method('getId')->willReturn('TestId');
        $this->assertEquals(null, $this->orderIdSaveQueueProcessCron->addOrderDataInTempTable([$this->order]));
    } 

    /**
     * Test addOrderDataInTempTable.
     *
     * @return void
     */
    public function testAddOrderDataInTempTableWithErrors()
    {
        $phrase = new Phrase(__('Something went wrong. Please try again later.'));
        $exception = new \Exception();
        $this->orderFactory->expects($this->any())
        ->method('create')->willReturn($this->order);
        $this->order->expects($this->any())
        ->method('getCollection')->willReturn($this);
        $this->orderCompressionFactory->expects($this->any())
        ->method('create')->willThrowException($exception);
        $this->assertEquals(null, $this->orderIdSaveQueueProcessCron->addOrderDataInTempTable([$this->order]));
    } 

}
