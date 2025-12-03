<?php
namespace Fedex\OptimizeProductinstance\Test\Unit\Cron;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\Quote;
use Fedex\OptimizeProductinstance\Model\QuoteCompressionFactory;
use Fedex\OptimizeProductinstance\Model\QuoteCompression;
use Magento\Quote\Model\ResourceModel\Quote\CollectionFactory as QuoteCollectionFactory;
use Fedex\OptimizeProductinstance\Model\ResourceModel\QuoteCompression\CollectionFactory as QuoteCompressionCollectionFactory;
use Fedex\OptimizeProductinstance\Helper\OptimizeItemInstanceHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\ResourceConnection;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\OptimizeProductinstance\Cron\QuoteIdSaveQueueProcessCron;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class QuoteIdSaveQueueProcessCronTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quote;
    protected $quoteCompressionFactory;
    protected $quoteCompression;
    protected $quoteCompressionCollection;
    protected $selectMock;
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $quoteIdSaveQueueProcessCron;
    public const EXPLORERS_OPTIMIZE_QUOTES_AND_ORDERS = "explorers_optimize_quotes_and_orders_within_14_months";

    /**
     * @var ToggleConfig $toggleConfig
     */
    protected $toggleConfig;

    /**
     * @var QuoteCollectionFactory $quoteCollectionFactory
     */
    protected $quoteCollectionFactory;

    /**
     * @var uoteCompressionCollectionFactory $quoteCompressionCollectionFactory
     */
    protected $quoteCompressionCollectionFactory;

    /**
     * @var ResourceConnection $resourceConnection
     */
    protected $resourceConnection;

    /**
     * @var QuoteFactory $quoteFactory
     */
    protected $quoteFactory;

    /**
     * @var LoggerInterface $logger
     */
    protected $logger;

    /**
     * @var OptimizeItemInstanceHelper $optimizeItemInstanceHelper
     */
    protected $optimizeItemInstanceHelper;


    /**
     * Description Creating mock for the variables
     * {@inheritdoc}
     *
     * @return MockBuilder
     */

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->quote = $this->getMockBuilder(Quote::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCollection', 'getId','addFieldToSelect','getSelect','addFieldToFilter'])
        ->getMock();
        $this->quoteCompressionFactory = $this->getMockBuilder(QuoteCompressionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->quoteCompression = $this->getMockBuilder(QuoteCompression::class)
        ->disableOriginalConstructor()
        ->setMethods(['getCollection','save', 'getId'])
        ->getMock();
        $this->quoteCompressionCollection = $this->getMockBuilder(AbstractCollection::class)
        ->disableOriginalConstructor()
        ->setMethods(['addFieldToSelect','getSelect','addFieldToFilter'])
        ->getMock();
        $this->quoteCollectionFactory = $this->getMockBuilder(QuoteCollectionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->quoteCompressionCollectionFactory = $this->getMockBuilder(QuoteCompressionCollectionFactory::class)
        ->disableOriginalConstructor()
        ->setMethods(['create'])
        ->getMock();
        $this->optimizeItemInstanceHelper = $this->getMockBuilder(OptimizeItemInstanceHelper::class)
        ->disableOriginalConstructor()
        ->setMethods(['pushTempQuoteCompressionIdQueue'])
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
        $this->quoteIdSaveQueueProcessCron    = $this->objectManager->getObject(
            QuoteIdSaveQueueProcessCron::class,
            [
                'quoteCollectionFactory'               => $this->quoteCollectionFactory,
                'quoteCompressionCollectionFactory'    => $this->quoteCompressionCollectionFactory,
                'resourceConnection'                   => $this->resourceConnection,
                'quoteFactory'                         => $this->quoteFactory,
                'quoteCompressionFactory'              => $this->quoteCompressionFactory,
                'optimizeItemInstanceHelper'           => $this->optimizeItemInstanceHelper,
                'toggleConfig'                         => $this->toggleConfig,
                'loggerMock'                           => $this->loggerMock
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
        $this->quoteFactory->expects($this->any())
        ->method('create')->willReturn($this->quote);
        $this->quote->expects($this->any())
        ->method('getCollection')->willReturnSelf();
        $this->quoteCompressionFactory->expects($this->any())
        ->method('create')->willReturn($this->quoteCompression);
        $this->quoteCompression->expects($this->any())
        ->method('getCollection')->willReturn($this->quoteCompressionCollection);
        $this->quoteCompressionCollection->expects($this->any())
        ->method('addFieldToSelect')->willReturnSelf();
        $this->quoteCompressionCollection->expects($this->any())
        ->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
        ->method('__toString')->willReturn('Test String');
        $this->resourceConnection->expects($this->any())
        ->method('getTableName')->willReturnSelf();
        $this->quote->expects($this->any())
        ->method('addFieldToSelect')->willReturnSelf();
        $this->quote->expects($this->any())
        ->method('addFieldToFilter')->willReturnSelf();
        $this->quote->expects($this->any())
        ->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())
        ->method('joinLeft')->willReturnSelf();
        $this->selectMock->expects($this->any())
        ->method('where')->willReturnSelf();
        $this->selectMock->expects($this->any())
        ->method('limit')->willReturnSelf();
        $this->assertEquals(true, $this->quoteIdSaveQueueProcessCron->execute());
    }

    /**
     * Test addOrderDataInTempTable.
     *
     * @return void
     */
    public function testAddOrderDataInTempTable()
    {
        $this->quoteFactory->expects($this->any())
        ->method('create')->willReturn($this->quote);
        $this->quote->expects($this->any())
        ->method('getCollection')->willReturn($this);
        $this->quoteCompressionFactory->expects($this->any())
        ->method('create')->willReturn($this->quoteCompression);
        $this->quoteCompression->expects($this->any())
        ->method('save')->willReturnSelf();
        $this->quoteCompression->expects($this->any())
        ->method('getId')->willReturn('TestId');
        $this->optimizeItemInstanceHelper->expects($this->any())
        ->method('pushTempQuoteCompressionIdQueue')->willReturnSelf();
        $this->assertEquals(null, $this->quoteIdSaveQueueProcessCron->addQuoteDataInTempTable([$this->quote]));
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
        $this->quoteFactory->expects($this->any())
        ->method('create')->willReturn($this->quote);
        $this->quote->expects($this->any())
        ->method('getCollection')->willReturn($this);
        $this->quote->expects($this->any())
        ->method('getId')->willReturn(123);
        $this->quoteCompressionFactory->expects($this->any())
        ->method('create')->willThrowException($exception);
        $this->assertEquals(null, $this->quoteIdSaveQueueProcessCron->addQuoteDataInTempTable([$this->quote]));
    } 

}
