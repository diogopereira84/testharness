<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Test\Unit\Controller\Index;

use Fedex\OrderApprovalB2b\Controller\Index\ApproveOrder;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Fedex\OrderApprovalB2b\Helper\OrderApprovalHelper;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\OrderApprovalB2b\Helper\RevieworderHelper;
use Fedex\OrderApprovalB2b\Helper\AdminConfigHelper;
use Fedex\SubmitOrderSidebar\Model\SubmitOrderApi as SubmitOrderModelAPI;
use Fedex\Delivery\Helper\Data as DeliveryHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Test class for ApproveOrder
 */
class ApproveOrderTest extends TestCase
{
    protected $revieworderHelper;
    protected $jsonMock;
    protected $orderPaymentInterface;
    /**
     * @var Context|MockObject
     */
    protected $context;

    /**
     * @var ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var ApproveOrder|MockObject
     */
    protected $approveOrder;

    /**
     * @var LoggerInterface|MockObject
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    protected $orderRepository;

    /**
     * @var OrderApprovalHelper|MockObject
     */
    protected $orderApprovalHelper;

    /**
     * @var SubmitOrderBuilder|MockObject
     */
    protected $submitOrderBuilder;

    /**
     * @var CartRepositoryInterface|MockObject
     */
    protected $quoteRepository;

    /**
     * @var JsonFactory|MockObject
     */
    protected JsonFactory $resultJsonFactory;

    /**
     * @var SubmitOrderModelAPI|MockObject
     */
    protected $submitOrderModelApi;

    /**
     * @var AdminConfigHelper|MockObject
     */
    protected $adminConfigHelper;

    /**
     * @var RequestInterface|MockObject
     */
    protected $requestMock;

    /**
     * @var DeliveryHelper|MockObject
     */
    protected $deliveryDataHelperMock;

    /**
     * @var CustomerSession|MockObject
     */
    protected $customerSession;

    /**
     * @var OrderInterface|MockObject
     */
    protected $orderMock;

    /**
     * Test setUp
     */
    public function setUp(): void
    {
        $this->orderRepository = $this->getMockBuilder(OrderRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'get',
                'save'
            ])
            ->getMockForAbstractClass();

        $this->orderApprovalHelper = $this->getMockBuilder(OrderApprovalHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'prepareOrderPickupRequest',
                'prepareOrderShippingRequest',
                'getErrorResponseMsgs'
            ])
            ->getMock();

        $this->orderMock = $this->getMockBuilder(OrderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'setEstimatedPickupTime',
                'getStatus',
                'getQuoteId',
                'getShippingMethod',
                'getPayment',
                'getFedexAccountNumber',
                'getIncrementId'
            ])
            ->getMockForAbstractClass();

        $this->submitOrderBuilder = $this->getMockBuilder(SubmitOrderBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['build'])
            ->getMock();

        $this->quoteRepository = $this->getMockBuilder(CartRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->revieworderHelper = $this->getMockBuilder(RevieworderHelper::class)
            ->disableOriginalConstructor()
            ->setMethods([
                'checkIfUserHasReviewOrderPermission',
                'sendResponseData'
            ])
            ->getMock();

        $this->submitOrderModelApi = $this->getMockBuilder(SubmitOrderModelApi::class)
            ->disableOriginalConstructor()
            ->setMethods(['updateQuoteStatusAndTimeoutFlag'])
            ->getMock();

        $this->adminConfigHelper = $this->getMockBuilder(AdminConfigHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getB2bOrderApprovalConfigValue'])
            ->getMock();

        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPost'])
            ->getMockForAbstractClass();

        $this->deliveryDataHelperMock = $this->getMockBuilder(DeliveryHelper::class)
            ->setMethods([
                'getToggleConfigurationValue',
                'isSelfRegCustomerAdminUser',
                'checkPermission'
            ])
            ->disableOriginalConstructor()
            ->getMock();

        $this->customerSession = $this->getMockBuilder(CustomerSession::class)
            ->disableOriginalConstructor()
            ->setMethods(['setSuccessErrorData'])->getMock();

        $this->context = $this->getMockBuilder(Context::class)
            ->setMethods(['getRequest','getPost'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->resultJsonFactory = $this->getMockBuilder(JsonFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->setMethods(['create', 'setData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderPaymentInterface = $this->getMockBuilder(OrderPaymentInterface::class)
            ->setMethods(['getFedexAccountNumber','getPayment'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->objectManager = new ObjectManager($this);
        $this->approveOrder = $this->objectManager->getObject(
            ApproveOrder::class,
            [
                'context' => $this->context,
                'orderRepository' => $this->orderRepository,
                'orderApprovalHelper' => $this->orderApprovalHelper,
                'submitOrderBuilder' => $this->submitOrderBuilder,
                'quoteRepository' => $this->quoteRepository,
                'resultJsonFactory' => $this->resultJsonFactory,
                'revieworderHelper' => $this->revieworderHelper,
                'submitOrderModelApi' => $this->submitOrderModelApi,
                'adminConfigHelper' => $this->adminConfigHelper,
                '_request' => $this->requestMock
            ]
        );
    }

    /**
     * Test method for Execute function
     *
     * @return void
     */
    public function testExecute()
    {
        $pickupReqData = '{"paymentData":"test"}';
        $rateResponse = [
                'output' => [
                    'rateQuote' => [
                        'rateQuoteDetails' => [
                            [
                                'deliveryLines' => [
                                    [
                                        'estimatedDeliveryLocalTime' => '2024-09-21T12:00:00'
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]
        ];

        $formattedDateTime = "test time";
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('12345');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending_approval');
        $this->orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn('123');
        $this->orderMock->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('fedexshipping_PICKUP');
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->orderApprovalHelper->expects($this->any())
            ->method('prepareOrderPickupRequest')
            ->willReturn($pickupReqData);
        $this->submitOrderBuilder->expects($this->any())
            ->method('build')
            ->willReturn([0 =>'', 'rateQuoteResponse' => $rateResponse]);
        $this->adminConfigHelper->expects($this->any())
            ->method('getB2bOrderApprovalConfigValue')
            ->willReturn('test');
        $this->orderMock->expects($this->any())
            ->method('setEstimatedPickupTime')
            ->with($formattedDateTime);
        $this->orderRepository->expects($this->any())
            ->method('save')
            ->with($this->orderMock);
        $this->submitOrderModelApi->expects($this->any())
            ->method('updateQuoteStatusAndTimeoutFlag')
            ->willReturnSelf();

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with shipping
     *
     * @return void
     */
    public function testExecutewithShipping()
    {
        $shippingReqData = '{"paymentData":"test"}';

        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('12345');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending_approval');
        $this->orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn('123');
        $this->orderMock->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('');
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->orderApprovalHelper->expects($this->any())
            ->method('prepareOrderShippingRequest')
            ->willReturn($shippingReqData);
        $this->orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->once())
            ->method('getFedexAccountNumber')
            ->willReturn('653243286');
        $this->submitOrderBuilder->expects($this->any())
            ->method('build')
            ->willReturn([0 =>'', 'rateQuoteResponse' => '']);
        $this->adminConfigHelper->expects($this->any())
            ->method('getB2bOrderApprovalConfigValue')
            ->willReturn('test');
        $this->submitOrderModelApi->expects($this->any())
            ->method('updateQuoteStatusAndTimeoutFlag')
            ->willReturnSelf();

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with Error response.
     *
     * @return void
     */
    public function testExecutewithError()
    {
        $shippingReqData = '{"shipmentData":"test"}';

        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('12345');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending_approval');
        $this->orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn('123');
        $this->orderMock->expects($this->once())
            ->method('getShippingMethod')
            ->willReturn('');
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturnSelf();
        $this->orderApprovalHelper->expects($this->any())
            ->method('prepareOrderShippingRequest')
            ->willReturn($shippingReqData);
        $this->orderApprovalHelper->expects($this->any())
            ->method('getErrorResponseMsgs')
            ->willReturn(true);
        $this->orderMock->expects($this->once())
            ->method('getPayment')
            ->willReturn($this->orderPaymentInterface);
        $this->orderPaymentInterface->expects($this->once())
            ->method('getFedexAccountNumber')
            ->willReturn('653243286');
        $this->submitOrderBuilder->expects($this->any())
            ->method('build')
            ->willReturn(['error' =>true, 'msg' => 'Failure']);
        $this->submitOrderModelApi->expects($this->any())
            ->method('updateQuoteStatusAndTimeoutFlag')
            ->willReturnSelf();

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with exception
     *
     * @return void
     */
    public function testExecutewithException()
    {
        $phrase = new Phrase(__('Exception message'));
        $exception = new LocalizedException($phrase);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('1234');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getIncrementId')
            ->willReturn('123');
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willThrowException($exception);

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with PermissionDenied
     *
     * @return void
     */
    public function testExecutewithPermissionDenied()
    {
        $resData = [
            'success' => false,
            'msg' => "System error. Please Try Again"
        ];
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('1234');
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(false);
        $this->revieworderHelper->expects($this->any())
            ->method('sendResponseData')
            ->willReturn($resData);
        $this->customerSession->expects($this->any())
            ->method('setSuccessErrorData')
            ->willReturnSelf();
        $this->jsonMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();

        $this->assertNotNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with Invalid Order Id
     *
     * @return void
     */
    public function testExecutewithInvalidOrderId()
    {
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('');

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with Invalid Order
     *
     * @return void
     */
    public function testExecutewithInvalidOrder()
    {
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('1234');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with Declined Order
     *
     * @return void
     */
    public function testExecutewithDeclinedOrder()
    {
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('12345');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->any())
            ->method('getStatus')
            ->willReturn('declined');

        $this->assertNull($this->approveOrder->execute());
    }

    /**
     * Test method for Execute with Invalid Quote
     *
     * @return void
     */
    public function testExecutewithInvalidQuote()
    {
        $this->revieworderHelper->expects($this->once())
            ->method('checkIfUserHasReviewOrderPermission')
            ->willReturn(true);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);
        $this->requestMock->expects($this->once())
            ->method('getPost')
            ->willReturn('12345');
        $this->orderRepository->expects($this->once())
            ->method('get')
            ->willReturn($this->orderMock);
        $this->orderMock->expects($this->once())
            ->method('getStatus')
            ->willReturn('pending_approval');
        $this->orderMock->expects($this->once())
            ->method('getQuoteId')
            ->willReturn('123');
        $this->quoteRepository->expects($this->once())
            ->method('get')
            ->willReturn(null);

        $this->assertNull($this->approveOrder->execute());
    }
}
