<?php
/**
 * Copyright © Fedex, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\OrderApprovalB2b\Test\Unit\ViewModel;

use Fedex\OrderApprovalB2b\ViewModel\OrderApprovalViewModel;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\OrderApprovalB2b\Helper\OrderApprovalHelper;
use Fedex\OrderApprovalB2b\Helper\OrderEmailHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\DataObjectFactory;
use Magento\Quote\Model\Quote;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;
use Exception;
use Fedex\Shipment\Model\ProducingAddressFactory;
use Magento\Framework\Model\AbstractModel;
use Magento\Directory\Model\Country;

class OrderApprovalViewModelTest extends TestCase
{
    protected $uploadToQuoteViewModelMock;
    protected $orderApprovalHelperMoock;
    protected $dataObjectFactory;
    protected $quote;
    protected $country;
    /**
     * @var (\Magento\Framework\Model\AbstractModel & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $producingAddressModel;
    /**
     * @var OrderApprovalViewModel $orderApprovalViewModel
     */
    private $orderApprovalViewModel;

    /**
     * @var  UploadToQuoteViewModel $uploadToQuoteViewModel
     */
    protected $uploadToQuoteViewModel;

    /**
     * @var  AdminConfigHelper $adminConfigHelperMock
     */
    protected $adminConfigHelperMock;

    /**
     * @var  OrderApprovalHelper $orderApprovalHelperMock
     */
    protected $orderApprovalHelperMock;

    /**
     * @var  OrderEmailHelper $orderEmailHelperMock
     */
    protected $orderEmailHelper;

    /**
     * @var StoreManagerInterface $storeManager
     */
    protected $storeManager;

    /**
     * @var Order $order
     */
    protected $order;

    /**
     * @var OrderInterface $orderInterface
     */
    protected $orderInterface;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ProducingAddressFactory
     */
    protected $producingAddressFactoryMock;

    /**
     * Setup method
     */
    protected function setUp(): void
    {
        $this->adminConfigHelperMock = $this->createMock(AdminConfigHelper::class);
        $this->uploadToQuoteViewModelMock = $this->createMock(UploadToQuoteViewModel::class);
        $this->orderApprovalHelperMoock = $this->createMock(OrderApprovalHelper::class);
        $this->orderEmailHelper = $this->createMock(OrderEmailHelper::class);

        $this->dataObjectFactory = $this->getMockBuilder(DataObjectFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->storeManager = $this->getMockBuilder(StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStore', 'getId'])
            ->getMockForAbstractClass();

        $this->quote = $this->getMockBuilder(Quote::class)
            ->setMethods(['getData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderInterface = $this->getMockBuilder(OrderInterface::class)
            ->setMethods(['loadByIncrementId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->order = $this->getMockBuilder(Order::class)
            ->setMethods(["loadByIncrementId", 'save', 'setEstimatedPickupTime', 'getShipmentsCollection','getId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->setMethods(["info"])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
           
        $this->producingAddressFactoryMock = $this->getMockBuilder(ProducingAddressFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addData', 'save', 'getCollection',
            'addFieldToFilter','load', 'getId', 'getData','setData'])
            ->getMock();

        $this->country = $this->getMockBuilder(Country::class)
            ->setMethods(['addData', 'save', 'load', 'getData'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->producingAddressModel = $this->createMock(AbstractModel::class);

        $this->orderApprovalViewModel = new OrderApprovalViewModel(
            $this->adminConfigHelperMock,
            $this->uploadToQuoteViewModelMock,
            $this->orderApprovalHelperMoock,
            $this->storeManager,
            $this->orderEmailHelper,
            $this->orderInterface,
            $this->logger,
            $this->producingAddressFactoryMock
        );
    }

    /**
     * Test isOrderApprovalB2bToggleEnabled
     *
     * @return void
     */
    public function testIsOrderApprovalB2bToggleEnabled()
    {
        $this->adminConfigHelperMock->expects($this->once())
            ->method('isOrderApprovalB2bEnabled')
            ->willReturn(true);

        $result = $this->orderApprovalViewModel->isOrderApprovalB2bEnabled();

        $this->assertTrue($result);
    }

    /**
     * Test checkoutQuotePriceisDashable
     *
     * @return void
     */
    public function testCheckoutQuotePriceisDashable()
    {
        $this->uploadToQuoteViewModelMock->expects($this->once())
            ->method('checkoutQuotePriceisDashable')
            ->willReturn(true);
        $result = $this->orderApprovalViewModel->checkoutQuotePriceisDashable();

        $this->assertTrue($result);
    }

    /**
     * Test getPendingOrderApprovalMsgTitle
     *
     * @return void
     */
    public function testGetPendingOrderApprovalMsgTitle()
    {
        $title = "Pending Approval";
        $result = $this->orderApprovalViewModel->getPendingOrderApprovalMsgTitle();

        $this->assertNotTrue($title, $result);
    }

    /**
     * Test getPendingOrderApprovalMsg
     *
     * @return void
     */
    public function testGetPendingOrderApprovalMsg()
    {
        $msg = "This order will require admin approval before we begin processing.
        The estimated delivery/pickup date and time may vary based on when this order is approved.";
        $result = $this->orderApprovalViewModel->getPendingOrderApprovalMsg();

        $this->assertNotTrue($msg, $result);
    }

    /**
     * Test getOrderPendingApproval
     *
     * @return void
     */
    public function testGetOrderPendingApproval()
    {
        $paymentData = (object)[
            "fedexAccountNumber" => 653243286,
            "paymentMethod" => 'fedex',
            "poReferenceId" => '1234',
            "number" => '8599'
        ];
        $this->dataObjectFactory->expects($this->any())->method('create')->willReturnSelf();
        $this->orderApprovalHelperMoock->expects($this->any())
            ->method('buildOrderSuccessResponse')
            ->willReturn([]);

        $this->assertIsArray($this->orderApprovalViewModel
            ->getOrderPendingApproval(
                $this->dataObjectFactory,
                $paymentData,
                $this->quote
            ));
    }

    /**
     * Test getB2bOrderApprovalConfigValue
     *
     * @return void
     */
    public function testGetB2bOrderApprovalConfigValue()
    {

        $returnData = 'Pending approval toast message';
        $this->storeManager->expects($this->once())->method('getStore')->willReturnSelf();
        $this->storeManager->expects($this->once())->method('getId')->willReturn(2);
        $this->adminConfigHelperMock->expects($this->once())
            ->method('getB2bOrderApprovalConfigValue')->willReturn($returnData);

        $this->assertEquals(
            $returnData,
            $this->orderApprovalViewModel->getB2bOrderApprovalConfigValue('order_success_toast_msg')
        );
    }

    /**
     * Test b2bOrderSendEmail
     *
     * @return void
     */
    public function testB2bOrderSendEmail()
    {
        $this->orderEmailHelper->expects($this->once())
            ->method('sendOrderGenericEmail')
            ->willReturnSelf();
        $orderData = ['order_id' => 41193, 'status' => 'confirmed'];

        $this->assertNull($this->orderApprovalViewModel->b2bOrderSendEmail($orderData));
    }

    /**
     * Test getOrder
     *
     * @return void
     */
    public function testGetOrder()
    {
        $this->order->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);

        $this->assertNull($this->orderApprovalViewModel->getOrder('101001'));
    }

    /**
     * Test getOrder
     *
     * @return void
     */
    public function testGetOrderWithNull()
    {
        $this->order->expects($this->any())
            ->method("loadByIncrementId")
            ->willReturn($this->orderInterface);

        $this->assertNull($this->orderApprovalViewModel->getOrder(''));
    }

    /**
     * Test saveEstimatedPickupTime
     *
     * @return void
     */
    public function testSaveEstimatedPickupTime()
    {
        $response = [
            'response' => [
                'output' => [
                    'rateQuote' => [
                        'rateQuoteDetails' => [
                            [
                                'deliveryLines' => [
                                    [
                                        'estimatedDeliveryLocalTime' => '2024-09-26 17:00:00'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $expectedFormattedDate = 'Thursday, September 26, 5:00pm';
        $this->order->expects($this->any())
            ->method('setEstimatedPickupTime')
            ->with($expectedFormattedDate);
        $this->order->expects($this->any())
            ->method('save');
        $this->logger->expects($this->any())
            ->method('info');
        $this->testsaveDataInOrderProductingTable();

        $this->orderApprovalViewModel->SaveEstimatedPickupTime($response, $this->order);
    }

    /**
     * Test testSaveEstimatedPickupTimeWithEmptyDeliveryTime
     *
     * @return void
     */
    public function testSaveEstimatedPickupTimeWithEmptyDeliveryTime()
    {
        $response = [
            'response' => [
                'output' => [
                    'rateQuote' => [
                        'rateQuoteDetails' => [
                            [
                                'deliveryLines' => []
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->orderApprovalViewModel->SaveEstimatedPickupTime($response, $this->order);
    }

    /**
     * Test testSaveEstimatedPickupTimeWithException
     *
     * @return void
     */
    public function testSaveEstimatedPickupTimeWithException()
    {
        $response = [
            'response' => [
                'output' => [
                    'rateQuote' => [
                        'rateQuoteDetails' => [
                            [
                                'deliveryLines' => [
                                    [
                                        'estimatedDeliveryLocalTime' => '2024-09-26 17:00:00'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $expectedFormattedDate = 'Thursday, September 26, 5:00pm';
        $this->order->expects($this->once())
            ->method('setEstimatedPickupTime')
            ->with($expectedFormattedDate)
            ->willReturnSelf();
        $this->order->expects($this->once())
            ->method('save')
            ->willThrowException(new Exception('Test exception'));
        $this->logger->expects($this->once())
            ->method('critical')
            ->with(
                $this->stringContains(
                    'Exception saving estimatedDeliveryLocalTime after order approval: Test exception'
                )
            );

        $this->orderApprovalViewModel->SaveEstimatedPickupTime($response, $this->order);
    }

     /**
      * Test case for getOrderProducingAddressIdByOrderId
      */
    public function testsaveDataInOrderProductingTable()
    {
        $addtionalData = '{
            "estimated_time":"23:12L45"
        }';
        $this->producingAddressFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('load')
        ->willReturn($this->producingAddressFactoryMock);
        $this->producingAddressFactoryMock->expects($this->any())->method('getId')->willReturn(12);
        $this->producingAddressFactoryMock->expects($this->any())->method('create')->willReturn($this->country);
        $this->country->expects($this->any())->method('load')->willReturn(json_decode(json_encode($this->country)));
        $this->producingAddressFactoryMock->expects($this->any())->method('getData')
        ->willReturn($addtionalData);
    }

    /**
     * Test case for getOrderProducingAddressIdByOrderId
     */
    public function testgetOrderProducingAddressIdByOrderId()
    {
        $this->producingAddressFactoryMock->expects($this->any())->method('create')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('load')
        ->willReturn([$this->producingAddressFactoryMock]);
        $this->producingAddressFactoryMock->expects($this->any())->method('getId')->willReturn(12);
        $this->orderApprovalViewModel->getOrderProducingAddressIdByOrderId(12);
    }

    /**
     * Test case for getOrderProducingAddressIdByOrderIdWithException
     */
    public function testgetOrderProducingAddressIdByOrderIdWithException()
    {
    
        $this->producingAddressFactoryMock->expects($this->any())
        ->method('create')->willThrowException(new Exception('Test exception'));
        $this->producingAddressFactoryMock->expects($this->any())->method('getCollection')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('addFieldToFilter')->willReturnSelf();
        $this->producingAddressFactoryMock->expects($this->any())->method('load')
        ->willReturn([$this->producingAddressFactoryMock]);
        $this->producingAddressFactoryMock->expects($this->any())->method('getId')->willReturn(12);
        $this->orderApprovalViewModel->getOrderProducingAddressIdByOrderId(12);
    }
}
