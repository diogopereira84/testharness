<?php
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\CartGraphQl\Model\PlaceOrder\PoliticalDisclosureService;
use Fedex\CartGraphQl\Model\Resolver\AddOrUpdatePoliticalDisclosure;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory;
use Fedex\GraphQl\Model\NewRelicHeaders;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Sales\Model\Order;
use Fedex\InStoreConfigurations\Model\System\Config;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Query\Resolver\BatchRequestItemInterface;
use Magento\Store\Model\StoreManagerInterface;

class AddOrUpdatePoliticalDisclosureTest extends TestCase
{
    private PoliticalDisclosureService $politicalDisclosureServiceMock;
    private Config $instoreConfigMock;
    private GraphQlBatchRequestCommandFactory $requestCommandFactoryMock;
    private ValidationBatchComposite $validationCompositeMock;
    private BatchResponseFactory $batchResponseFactoryMock;
    private BatchResponse $batchResponseMock;
    private LoggerHelper $loggerHelperMock;
    private NewRelicHeaders $newRelicHeadersMock;
    private ContextInterface $contextMock;
    private Field $fieldMock;
    private AddOrUpdatePoliticalDisclosure $resolver;
    private StoreManagerInterface $storeManager;

    protected function setUp(): void
    {
        $this->politicalDisclosureServiceMock = $this->createMock(PoliticalDisclosureService::class);

        // Mock InstoreConfig for ONLY the method we need
        $this->instoreConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isEnablePoliticalDisclosureInPlaceOrder'])
            ->getMock();
        $this->instoreConfigMock->method('isEnablePoliticalDisclosureInPlaceOrder')->willReturn(true);

        $this->requestCommandFactoryMock = $this->createMock(GraphQlBatchRequestCommandFactory::class);
        $this->validationCompositeMock = $this->createMock(ValidationBatchComposite::class);
        $this->batchResponseFactoryMock = $this->createMock(BatchResponseFactory::class);
        $this->batchResponseMock = $this->createMock(BatchResponse::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->newRelicHeadersMock = $this->createMock(NewRelicHeaders::class);
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->storeManager = $this->createMock(StoreManagerInterface::class);

        $this->resolver = new AddOrUpdatePoliticalDisclosure(
            $this->politicalDisclosureServiceMock,
            $this->instoreConfigMock,
            $this->storeManager,
            $this->requestCommandFactoryMock,
            $this->validationCompositeMock,
            $this->batchResponseFactoryMock,
            $this->loggerHelperMock,
            $this->newRelicHeadersMock
        );
    }

    public function testProceedSuccessful(): void
    {
        $orderId = '2020296880177622';
        $orderEntityId = "100";
        $disclosureData = ['status' => 1];

        $this->politicalDisclosureServiceMock->method('setDisclosureDetails')
            ->with($disclosureData, $orderId)
            ->willReturn(true);

        $this->politicalDisclosureServiceMock->method('getDisclosureDetailsByOrderId')
            ->with($orderEntityId)
            ->willReturn($disclosureData);

        $this->batchResponseFactoryMock->method('create')
            ->willReturn($this->batchResponseMock);

        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with(
                $this->anything(),
                $this->callback(fn($response) => isset($response['order']['order_number']) && $response['order']['order_number'] === $orderId)
            );

        // Mock request command with dynamically added getArgs
        $mockRequest = $this->getMockBuilder(BatchRequestItemInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getArgs'])
            ->getMockForAbstractClass();

        $mockRequest->method('getArgs')->willReturn([
            'input' => [
                'order_id' => $orderId,
                'political_disclosure' => $disclosureData
            ]
        ]);

        $result = $this->resolver->proceed(
            $this->contextMock,
            $this->fieldMock,
            [$mockRequest],
            []
        );

        $this->assertInstanceOf(BatchResponse::class, $result);
    }

    public function testProceedExceptionDuringSaveThrowsGraphQlInputException(): void
    {
        $orderId = '2020296880177622';

        $this->politicalDisclosureServiceMock->method('setDisclosureDetails')
            ->willThrowException(new \Exception('DB save error'));

        $mockRequest = $this->getMockBuilder(\Fedex\GraphQl\Model\GraphQlBatchRequestCommand::class)
            ->disableOriginalConstructor()
            ->addMethods(['getArgs'])
            ->getMock();
        $mockRequest->method('getArgs')->willReturn([
            'input' => [
                'order_id' => $orderId,
                'political_disclosure' => ['data' => 'sample']
            ]
        ]);

        $this->loggerHelperMock->expects($this->atLeastOnce())->method('error');

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Error on saving political disclosure: DB save error');

        $this->resolver->proceed(
            $this->contextMock,
            $this->fieldMock,
            [$mockRequest],
            []
        );
    }
}
