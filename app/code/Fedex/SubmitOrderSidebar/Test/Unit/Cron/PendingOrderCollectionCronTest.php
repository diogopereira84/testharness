<?php
/**
 * @category    Fedex
 * @package     Fedex_SubmitOrderSidebar
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Rutvee Sojitra <rutvee.sojitra.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\SubmitOrderSidebar\Test\Unit\Cron;

use Exception;
use Psr\Log\LoggerInterface;
use Magento\Framework\Phrase;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObject;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Fedex\SubmitOrderSidebar\Cron\PendingOrderCollectionCron;

class PendingOrderCollectionCronTest extends TestCase
{
    /**
     * @var (\PHPUnit\Framework\MockObject\MockObject & \Psr\Log\LoggerInterface)
     */
    protected $loggerMock;
    protected $deliveryHelperMock;
    protected $toggleConfigMock;
    protected $submitOrderModelAPIMock;
    protected $orderCollectionFactoryMock;
    protected $pendingOrderCronMock;
    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var DeliveryHelper|MockObject
     */
    protected $deliveryHelper;

    /**
     * @var ToggleConfig|MockObject
     */
    protected $toggleConfig;

    /**
     * @var SubmitOrderModelAPI|MockObject
     */
    protected $submitOrderModelAPI;

    /**
     * @var OrderCollectionFactory|MockObject
     */
    protected $orderCollectionFactory;

    public const GTN = '12345';

    /**
     * Main set up method
     */
    public function setUp() : void
    {
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->deliveryHelperMock = $this->createMock(DeliveryHelper::class);
        $this->toggleConfigMock = $this->createMock(ToggleConfig::class);
        $this->submitOrderModelAPIMock = $this->createMock(SubmitOrderModelAPI::class);
        $this->orderCollectionFactoryMock = $this->getMockBuilder(OrderCollectionFactory::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'create',
                'addFieldToSelect',
                'addFieldToFilter',
                'getSelect',
                'where',
                'getData',
            ])
            ->getMock();

        $this->pendingOrderCronMock = (new ObjectManager($this))->getObject(
            PendingOrderCollectionCron::class,
            [
                'logger' => $this->loggerMock,
                'deliveryHelper' => $this->deliveryHelperMock,
                'toggleConfig' => $this->toggleConfigMock,
                'submitOrderModelAPI' => $this->submitOrderModelAPIMock,
                'orderCollectionFactory' => $this->orderCollectionFactoryMock
            ]
        );
    }

    /**
     * Used to update pending to new
     */
    public function testGetPendingStatusOrderCollection()
    {
        $orderData = [
            0 => [
                "increment_id" => self::GTN,
                "quote_id" => '1234',
                "status" => '8599'
            ],
            1 =>  [
                "increment_id" => self::GTN,
                "quote_id" => '1234',
                "status" => '8599'
            ]
        ];

        $this->deliveryHelperMock->expects($this->any())->method("isEproCustomer")->willReturn(false);
        $this->toggleConfigMock->expects($this->any())->method("getToggleConfigValue")->willReturn(true);
        $this->orderCollectionFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->orderCollectionFactoryMock->expects($this->any())->method('addFieldToSelect')->willReturnSelf();
        $this->orderCollectionFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->orderCollectionFactoryMock->expects($this->any())->method("getSelect")->willReturnSelf();
        $this->orderCollectionFactoryMock->expects($this->any())->method("where")->willReturnSelf();
        $this->orderCollectionFactoryMock->expects($this->any())->method("getData")->willReturn($orderData);
        $this->testValidateOrderTransaction();

        $this->assertNotNull($this->pendingOrderCronMock->getPendingStatusOrderCollection());
    }

    /**
     * Used to update pending to new
     */
    public function testValidateOrderTransaction()
    {
        $this->submitOrderModelAPIMock->expects($this->any())->method("getRetailTransactionIdByGtnNumber")
        ->willReturn('');
        $this->assertNull($this->pendingOrderCronMock->validateOrderTransaction(self::GTN));
    }

    /**
     * Used to update pending to new
     */
    public function testGetPendingStatusOrderCollectionWithException()
    {
        $this->orderCollectionFactoryMock->expects($this->any())->method('create')
        ->willThrowException(new \Exception());
        $this->pendingOrderCronMock->getPendingStatusOrderCollection();
        $this->assertNotNull($this->pendingOrderCronMock->getPendingStatusOrderCollection());
    }
}
