<?php

/**
 * Copyright ©  All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\DbOptimization\Test\Unit\Cron;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\DbOptimization\Cron\QuoteItemOptionsCron;
use Magento\Framework\DB\Select;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Fedex\DbOptimization\Api\QuoteItemOptionsMessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface;

class QuoteItemOptionsCronTest extends TestCase
{
    protected $quoteCollectionMock;
    protected $qioMessageInterfaceMock;
    protected $publisherInterfaceMock;
    protected $selectMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $quoteItemOptionsCronMock;
    /**
     * @var \Magento\Framework\App\ResourceConnection|MockObject
     */
    protected $resourceConnectionMock;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json|MockObject
     */
    protected $jsonMock;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface|MockObject
     */
    protected $scopeConfigInterfaceMock;

    /**
     * @var \Fedex\EnvironmentManager\ViewModel\ToggleConfig|MockObject
     */
    protected $toggleConfigMock;

    /**
     * @var \Psr\Log\LoggerInterface|MockObject
     */
    protected $loggerMock;

    /**
     * @var \Magento\Quote\Model\Quote|MockObject
     */
    protected $quoteModelMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

         $this->resourceConnectionMock = $this->getMockBuilder(\Magento\Framework\App\ResourceConnection::class)
            ->setMethods(['getTableName'])
            ->disableOriginalConstructor()
            ->getMock();

         $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

         $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

         $this->quoteModelMock = $this->getMockBuilder(\Magento\Quote\Model\Quote::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

         $this->quoteCollectionMock = $this->getMockBuilder(\Magento\Quote\Model\ResourceModel\Quote\Collection::class)
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'getSelect', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

         $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(['serialize'])
            ->disableOriginalConstructor()
            ->getMock();

         $this->qioMessageInterfaceMock = $this->getMockBuilder(QuoteItemOptionsMessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();

         $this->publisherInterfaceMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

         $this->selectMock = $this->getMockBuilder(Select::class)
            ->setMethods(['join', 'columns', 'limit'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->quoteItemOptionsCronMock = $this->objectManager->getObject(
            QuoteItemOptionsCron::class,
            [
                'resourceConnection' => $this->resourceConnectionMock,
                'serializerJson' => $this->jsonMock,
                'message' => $this->qioMessageInterfaceMock,
                'publisher' => $this->publisherInterfaceMock,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'loggerInterface' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'quoteModel' => $this->quoteModelMock
            ]
        );
    }

    /**
     * Test Case for Execute in positive case
     *
     * @return null
     */
    public function testExecute()
    {
        $dummyData = [['entity_id'=> '1', 'updated_at' =>'2021-06-01', 'option_id' => '1']];
        $dummyDataSerialize = json_encode([['entity_id'=> '1', 'updated_at' =>'2021-06-01', 'option_id' => '1']]);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->resourceConnectionMock->expects($this->exactly(2))->method('getTableName')
            ->withConsecutive(['quote_item'],['quote_item_option'])->willReturnOnConsecutiveCalls(['quote_item'],['quote_item_option']);

        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')
            ->with('db_cleanup_configuration/cleaup_setting/prev_month')->willReturn('14');

        $this->quoteModelMock->expects($this->any())->method('getCollection')->willReturn($this->quoteCollectionMock);
        $this->quoteCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->quoteCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('join')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('columns')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('limit')->willReturnSelf();

        $this->quoteCollectionMock->expects($this->any())->method('getData')->willReturn($dummyData);
        $this->jsonMock->expects($this->any())->method('serialize')->willReturn($dummyDataSerialize);

        $this->qioMessageInterfaceMock->expects($this->any())->method('setMessage')->willReturn(null);
        $this->publisherInterfaceMock->expects($this->any())->method('publish')->willReturn(null);

        $this->assertEquals(null, $this->quoteItemOptionsCronMock->execute());
    }

    /**
     * Test Case for Execute with Toggle OFF
     *
     * @return null
     */
    public function testExecuteWithToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertEquals(null, $this->quoteItemOptionsCronMock->execute());
    }

    /**
     * Test Case for Execute if Cleaun month is less than 14
     *
     * @return null
     */
    public function testExecuteForCleanUpMonthLessThanFourteen()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')
            ->with('db_cleanup_configuration/cleaup_setting/prev_month')->willReturn('10');
        $this->assertEquals(null, $this->quoteItemOptionsCronMock->execute());
    }

    /**
     * Test Case for Execute if Records not found in database
     *
     * @return null
     */
    public function testExecuteIfRecordNotFound()
    {
        $dummyData = [];

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);
        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')
            ->with('db_cleanup_configuration/cleaup_setting/prev_month')->willReturn('14');

        $this->quoteModelMock->expects($this->any())->method('getCollection')->willReturn($this->quoteCollectionMock);
        $this->quoteCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->quoteCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();

        $this->quoteCollectionMock->expects($this->any())->method('getSelect')->willReturn($this->selectMock);
        $this->selectMock->expects($this->any())->method('join')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('columns')->willReturnSelf();
        $this->selectMock->expects($this->any())->method('limit')->willReturnSelf();
        $this->quoteCollectionMock->expects($this->any())->method('getData')->willReturn($dummyData);
        $this->assertEquals(null, $this->quoteItemOptionsCronMock->execute());
    }
}
