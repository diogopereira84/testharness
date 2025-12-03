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
use Fedex\DbOptimization\Cron\SalesOrderItemCron;
use Magento\Framework\DB\Select;
use Fedex\DbOptimization\Api\SalesOrderItemMessageInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection;

class SalesOrderItemCronTest extends TestCase
{

    protected $soiMessageInterfaceMock;
    protected $publisherInterfaceMock;
    protected $salesItemCollectionMock;
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;
    protected $salesItemCronMock;
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
     * @var \Magento\Sales\Model\Order\Item|MockObject
     */
    protected $salesOrderItemModelMock;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
         $this->soiMessageInterfaceMock = $this->getMockBuilder(SalesOrderItemMessageInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['setMessage'])
            ->getMockForAbstractClass();

          $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->setMethods(['serialize'])
            ->disableOriginalConstructor()
            ->getMock();

          $this->publisherInterfaceMock = $this->getMockBuilder(PublisherInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

          $this->scopeConfigInterfaceMock = $this->getMockBuilder(ScopeConfigInterface::class)
            ->setMethods(['getValue'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->toggleConfigMock = $this->getMockBuilder(ToggleConfig::class)
            ->disableOriginalConstructor()
            ->setMethods(['getToggleConfigValue'])
            ->getMock();

         $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

         $this->salesOrderItemModelMock = $this->getMockBuilder(\Magento\Sales\Model\Order\Item::class)
            ->setMethods(['getCollection'])
            ->disableOriginalConstructor()
            ->getMock();

         $this->salesItemCollectionMock = $this->getMockBuilder(Collection::class)
            ->setMethods(['addFieldToSelect', 'addFieldToFilter', 'setPageSize', 'setCurPage', 'load', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new ObjectManager($this);

        $this->salesItemCronMock = $this->objectManager->getObject(
            SalesOrderItemCron::class,
            [
                'message' => $this->soiMessageInterfaceMock,
                'serializerJson' => $this->jsonMock,
                'publisher' => $this->publisherInterfaceMock,
                'scopeConfigInterface' => $this->scopeConfigInterfaceMock,
                'loggerInterface' => $this->loggerMock,
                'toggleConfig' => $this->toggleConfigMock,
                'salesOrderItem' => $this->salesOrderItemModelMock
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
        $dummyData = [['item_id'=> '1', 'created_at' =>'2021-06-01', 'product_options' => []]];
        $dummyDataSerialize = json_encode($dummyData);

        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(true);

        $this->scopeConfigInterfaceMock->expects($this->any())->method('getValue')
            ->with('db_cleanup_configuration/cleaup_setting/prev_month')->willReturn('14');

        $this->salesOrderItemModelMock->expects($this->any())->method('getCollection')
                    ->willReturn($this->salesItemCollectionMock);
        $this->salesItemCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('setPageSize')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('setCurPage')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('load')->willReturnSelf();

        $this->salesItemCollectionMock->expects($this->any())->method('getData')->willReturn($dummyData);
        $this->jsonMock->expects($this->any())->method('serialize')->willReturn($dummyDataSerialize);

        $this->soiMessageInterfaceMock->expects($this->any())->method('setMessage')->willReturn(null);
        $this->publisherInterfaceMock->expects($this->any())->method('publish')->willReturn(null);

        $this->assertEquals(null, $this->salesItemCronMock->execute());
    }

    /**
     * Test Case for Execute with Toggle OFF
     *
     * @return null
     */
    public function testExecuteWithToggleOff()
    {
        $this->toggleConfigMock->expects($this->any())->method('getToggleConfigValue')->willReturn(false);
        $this->assertEquals(null, $this->salesItemCronMock->execute());
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
        $this->assertEquals(null, $this->salesItemCronMock->execute());
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

        $this->salesOrderItemModelMock->expects($this->any())->method('getCollection')
                ->willReturn($this->salesItemCollectionMock);
        $this->salesItemCollectionMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('setPageSize')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('setCurPage')->willReturnSelf();
        $this->salesItemCollectionMock->expects($this->any())->method('load')->willReturnSelf();

        $this->salesItemCollectionMock->expects($this->any())->method('getData')->willReturn($dummyData);

        $this->assertEquals(null, $this->salesItemCronMock->execute());
    }
}
