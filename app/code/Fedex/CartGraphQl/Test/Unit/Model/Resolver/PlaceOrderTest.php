<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Pedro Basseto <pbasseto@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\PlaceOrder\PoliticalDisclosureService;
use Fedex\CartGraphQl\Model\PlaceOrder\RequestData;
use Fedex\CartGraphQl\Model\PlaceOrder\SubmitOrder;
use Fedex\CartGraphQl\Model\Resolver\PlaceOrder;
use Fedex\GraphQl\Exception\GraphQlInStoreException;
use Fedex\GraphQl\Exception\GraphQlRAQMissingIdException;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Fedex\PoliticalDisclosure\Model\OrderDisclosure;
use Fedex\SelfReg\Controller\Landing\Mock;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Magento\NegotiableQuoteGraphQl\Model\NegotiableQuote\ResourceModel\QuoteIdMask;
use Magento\Quote\Model\QuoteFactory;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\UploadToQuote\ViewModel\UploadToQuoteViewModel;
use Fedex\Shipment\Helper\ShipmentEmail;
use Magento\Sales\Model\Order;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper;
use Fedex\FuseBiddingQuote\Helper\FuseBidHelper;

use Fedex\PoliticalDisclosure\Api\OrderDisclosureRepositoryInterface;
use Fedex\PoliticalDisclosure\Model\OrderDisclosureFactory;
use Fedex\PoliticalDisclosure\Model\Config\PoliticalDisclosureConfig;
use Magento\Framework\Exception\CouldNotSaveException;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;

class PlaceOrderTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;
    protected $requestDataMock;
    /**
     * @var (\Fedex\CartGraphQl\Helper\LoggerHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerHelperMock;
    private MockObject $fieldMock;

    private MockObject $contextMock;

    private MockObject $resolverInfoMock;

    private MockObject $requestCommandFactoryMock;

    private MockObject $validationCompositeMock;

    private MockObject $getCartForUserMock;

    private MockObject $extensionAttributesMock;

    private MockObject $quoteMock;

    protected PlaceOrder $placeOrder;
    protected MockObject $submitOrder;
    private MockObject $cartIntegrationMock;

    private MockObject $batchResponseMockFactory;

    private MockObject $batchResponseMock;

    private MockObject $quoteIdMaskResource;

    private MockObject $quoteFactory;

    private MockObject $fuseBidViewModel;

    private MockObject $uploadToQuoteViewModel;

    /**
     * @var (\Fedex\Shipment\Helper\ShipmentEmail & \PHPUnit\Framework\MockObject\MockObject)
     */

    protected $shipmentEmailMock;
    /**
     * @var (\Magento\Sales\Model\Order & \PHPUnit\Framework\MockObject\MockObject)
     */

    protected $orderMock;
    /**
     * @var ScopeConfigInterface & \PHPUnit\Framework\MockObject\MockObject
     */
    protected ScopeConfigInterface $configInterface;

    /**
     * @var OrderDisclosureRepositoryInterface
     */
    protected OrderDisclosureRepositoryInterface $disclosureRepository;

    /**
     * @var OrderDisclosureFactory
     */
    protected OrderDisclosureFactory $disclosureFactory;

    /**
     * @var PoliticalDisclosureService
     */
    protected PoliticalDisclosureService $politicalDisclosureConfig;

    /**
     * @var InstoreConfig
     */
    protected InstoreConfig $instoreConfig;

    /**
     * @var MockObject
     */
    protected MockObject $fuseBidGraphqlHelperMock;

    /**
     * @var MockObject
     */
    protected MockObject $fuseBidHelperMock;

    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestCommandMock = $this->createMock(RequestCommand::class);
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $this->validationCompositeMock = $this->createMock(ValidationComposite::class);
        $this->getCartForUserMock = $this->getMockBuilder(GetCartForUser::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->submitOrder = $this->getMockBuilder(SubmitOrder::class)
            ->onlyMethods(['execute'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->fieldMock = $this->createMock(Field::class);
        $this->contextMock = $this->getMockBuilder(ContextInterface::class)
            ->addMethods(['getExtensionAttributes', 'getUserId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->resolverInfoMock = $this->createMock(ResolveInfo::class);
        $this->extensionAttributesMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->addMethods(['getId'])
            ->onlyMethods(['getStore'])
            ->getMockForAbstractClass();
        $this->quoteMock = $this->getMockBuilder(Quote::class)
            ->onlyMethods(['getId', 'getAllItems', 'getData', 'getReservedOrderId', 'load'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartIntegrationMock = $this->createMock(CartIntegrationInterface::class);
        $this->requestDataMock = $this->createMock(RequestData::class);

        $this->requestDataMock->expects($this->any())->method('build')->willReturn((object) '{}');
        $this->batchResponseMockFactory = $this->createMock(BatchResponseFactory::class);
        $this->batchResponseMock = $this->createMock(BatchResponse::class);
        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);

        $this->quoteIdMaskResource = $this->createMock(QuoteIdMask::class);
        $this->quoteFactory = $this->getMockBuilder(QuoteFactory::class)
            ->setMethods(['create', 'load', 'getIsBid'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->quoteFactory->method('create')->willReturn($this->quoteMock);

        $this->quoteMock->method('load')->willReturn($this->quoteMock);
        $this->fuseBidViewModel = $this->getMockBuilder(FuseBidViewModel::class)
            ->setMethods(['isFuseBidToggleEnabled'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->uploadToQuoteViewModel = $this->createMock(UploadToQuoteViewModel::class);

        $this->shipmentEmailMock = $this->getMockBuilder(ShipmentEmail::class)
            ->setMethods(['sendEmail'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->orderMock = $this->getMockBuilder(Order::class)
            ->setMethods(['getId', 'getTotal', 'getItems', 'getIncrementId', 'loadByIncrementId'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->configInterface = $this->getMockBuilder(ScopeConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();

        $this->fuseBidGraphqlHelperMock = $this->createMock(\Fedex\FuseBiddingQuote\Helper\FuseBidGraphqlHelper::class);
        $this->fuseBidHelperMock = $this->createMock(\Fedex\FuseBiddingQuote\Helper\FuseBidHelper::class);

        $this->disclosureRepository = $this->createMock(OrderDisclosureRepositoryInterface::class);
        $this->disclosureFactory = $this->createMock(OrderDisclosureFactory::class);
        $this->instoreConfig = $this->createMock(InstoreConfig::class);
        $this->politicalDisclosureConfig = $this->createMock(PoliticalDisclosureService::class);

        $this->instoreConfig->method('isEnablePoliticalDisclosureInPlaceOrder')->willReturn(true);
        $this->placeOrder = new PlaceOrder(
            $this->getCartForUserMock,
            $this->submitOrder,
            $this->quoteIdMaskResource,
            $this->quoteFactory,
            $this->fuseBidViewModel,
            $this->uploadToQuoteViewModel,
            $this->instoreConfig,
            $this->politicalDisclosureConfig,
            $this->shipmentEmailMock,
            $this->requestCommandFactoryMock,
            $this->validationCompositeMock,
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->newRelicHeaders,
            $this->orderMock,
            $this->configInterface,
            $this->fuseBidGraphqlHelperMock,
            $this->fuseBidHelperMock,
        );

        $this->mockInitialData();
    }

    public function testPlaceOrder()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $this->submitOrder->expects($this->once())->method('execute')->willReturn([
            'rateQuoteResponse' => [
                'transactionId' => '187b9f7b-6771-4b5c-a6ad-ec12a36957b2'
            ]
        ]);

        $mockArgs = $this->getMockArgs();
        $mockArgs['input']['notes'] = [
            "text" => "Test Note",
            "audit" => [
                "user" => "Test User",
                "creationTime" => "2023-12-01T00:00:00Z",
                "userReference" => [
                    "reference" => "Testing",
                    "source" => "MAGENTO"
                ]
            ]
        ];

        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($mockArgs);

        $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);

        $this->fuseBidViewModel->expects($this->any())
            ->method('isFuseBidToggleEnabled')
            ->willReturn(true);
        $this->orderMock->expects($this->any())
            ->method('loadByIncrementId')
            ->willReturnSelf();
        $this->orderMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->configInterface->expects($this->any())
            ->method('getValue')
            ->willReturn(true);
        $data = $this->placeOrder->resolve(
            $this->contextMock,
            $this->fieldMock,
            $requests
        );
        $this->assertInstanceOf(BatchResponse::class, $data);
    }

    public function testPlaceOrderWithException()
    {
        $mockArgs = $this->getMockArgs();
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($mockArgs);

        $this->expectExceptionMessage('some message');
        $this->expectException(GraphQlInStoreException::class);

        $this->submitOrder->expects($this->once())->method('execute')
            ->willReturn(['error' => true, 'msg' => 'some message']);

            $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);

        $this->placeOrder->resolve(
            $this->contextMock,
            $this->fieldMock,
            $requests
        );
    }

    public function testPlaceOrderWithExceptionResponseError()
    {
        $mockArgs = $this->getMockArgs();
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($mockArgs);

        $this->expectExceptionMessage('some message');
        $this->expectException(GraphQlInStoreException::class);

        $this->submitOrder->expects($this->once())->method('execute')->willReturn([
            'error' => true,
            'response' => [
                'errors' => [
                    [
                        'message' => 'some message2'
                    ]
                ]
            ]
        ]);

        $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);

        $this->placeOrder->resolve(
            $this->contextMock,
            $this->fieldMock,
            $requests
        );
    }

    public function testPlaceOrderWithGraphQlFujitsuResponseException(): void
    {
        $mockArgs = $this->getMockArgs();
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class); // Adjust this with the correct class
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($mockArgs);

        $exception = new GraphQlFujitsuResponseException(__("Some message"));

        $this->submitOrder->expects($this->once())
            ->method('execute')
            ->willThrowException($exception);

        $this->expectException(GraphQlFujitsuResponseException::class);

        $mockArgs = $this->getMockArgs();
        $mockArgs['input']['notes'] = [
            "text" => "Test Note",
            "audit" => [
                "user" => "Test User",
                "creationTime" => "2023-12-01T00:00:00Z",
                "userReference" => [
                    "reference" => "Testing",
                    "source" => "MAGENTO"
                ]
            ]
        ];

        $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);

        $this->placeOrder->resolve(
            $this->contextMock,
            $this->fieldMock,
            $requests
        );
    }


    public function testPlaceOrderWithGraphQlRAQMissingIdException(): void
    {
        $mockArgs = $this->getMockArgs();
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $requestCommandMock = $this->createMock(GraphQlBatchRequestCommand::class); // Adjust this with the correct class
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($mockArgs);

        $this->submitOrder->expects($this->once())->method('execute')->willReturn([
            'error' => false
        ]);

        $this->expectException(GraphQlRAQMissingIdException::class);
        $this->getExpectedExceptionMessage("Transaction Id Missing from RAQ response.");

        $mockArgs = $this->getMockArgs();
        $mockArgs['input']['notes'] = [
            "text" => "Test Note",
            "audit" => [
                "user" => "Test User",
                "creationTime" => "2023-12-01T00:00:00Z",
                "userReference" => [
                    "reference" => "Testing",
                    "source" => "MAGENTO"
                ]
            ]
        ];

        $this->quoteFactory->expects($this->any())
            ->method('create')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('load')
            ->willReturnSelf();

        $this->quoteFactory->expects($this->any())
            ->method('getIsBid')
            ->willReturn(true);

        $this->placeOrder->resolve(
            $this->contextMock,
            $this->fieldMock,
            $requests
        );
    }

    protected function getMockData(): array
    {
        return [
            "order" => [
                "order_number" => "2010565483236658"
            ],
            "transaction_id" => "187b9f7b-6771-4b5c-a6ad-ec12a36957b2",
        ];
    }

    protected function getErrorMockData(): array
    {
        return [
            "order" => [
                "order_number" => "2010565483236658"
            ],
            "transaction_id" => '',
        ];
    }

    protected function getMockArgs(): array
    {
        return ['input' => ['cart_id' => 'fILVxdDqDNlE4YTIfzYFxeDAUImNjcCQ']];
    }

    protected function mockInitialData(): void
    {
        $this->extensionAttributesMock->expects($this->any())->method('getStore')->willReturnSelf();
        $this->extensionAttributesMock->expects($this->any())->method('getId')->willReturn(1);

        $this->quoteMock->expects($this->any())->method('getReservedOrderId')
            ->willReturn('2010565483236658');
        $this->quoteMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $this->getCartForUserMock->expects($this->any())->method('execute')->willReturn($this->quoteMock);

        $this->contextMock->expects($this->any())->method('getExtensionAttributes')
            ->willReturn($this->extensionAttributesMock);
    }
}
