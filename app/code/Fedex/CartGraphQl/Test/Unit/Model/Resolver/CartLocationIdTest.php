<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Model\Resolver;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\Validation\Validate\BatchValidateModel as ValidateModel;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Fedex\GraphQl\Model\Validation\ValidationBatchCompositeFactory as ValidationCompositeFactory;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

/**
 * @inheritdoc
 */
class CartLocationIdTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;
    /**
     * @var (\Fedex\CartGraphQl\Helper\LoggerHelper & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $loggerHelperMock;
    /**
     * @var CartLocationId
     */
    protected CartLocationId $cartLocationId;
    /**
     * @var GetCartForUser|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $getCartForUserMock;
    /**
     * @var Field|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldMock;
    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contextMock;
    /**
     * @var ResolveInfo|\PHPUnit\Framework\MockObject\MockObject
     */
    private $resolverInfoMock;
    /**
     * @var CartIntegrationRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartIntegrationRepositoryMock;
    /**
     * @var RequestCommandFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestCommandFactoryMock;
    /**
     * @var ValidationComposite|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validationCompositeMock;
    /**
     * @var ValidateModel|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validateModelMock;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\stdClass
     */
    private $modelMock;
    /**
     * @var CartIntegrationInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartIntegrationInterface;
    /**
     * @var ValidationCompositeFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validationCompositeFactoryMock;

    /**
     * @var BatchResponseFactory|MockObject
     */
    private BatchResponseFactory|MockObject $batchResponseMockFactory;

    /**
     * @var BatchResponse|MockObject|(BatchResponse&MockObject)
     */
    private BatchResponse|MockObject $batchResponseMock;

    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->cartIntegrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->onlyMethods(['getByQuoteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartIntegrationInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->onlyMethods(['getLocationId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestCommandFactoryMock = $this->getMockBuilder(RequestCommandFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $requestCommandMock = $this->getMockBuilder(RequestCommand::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->requestCommandFactoryMock->method('create')->willReturn($requestCommandMock);
        $this->validationCompositeMock = $this->getMockBuilder(ValidationComposite::class)
            ->onlyMethods(['validate'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validationCompositeFactoryMock = $this->getMockBuilder(ValidationCompositeFactory::class)
            ->onlyMethods(['create'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->validationCompositeFactoryMock->method('create')->willReturn($this->validationCompositeMock);
        $this->fieldMock = $this->getMockBuilder(Field::class)
            ->onlyMethods(['getName'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->resolverInfoMock = $this->getMockBuilder(ResolveInfo::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->modelMock = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $this->batchResponseMockFactory = $this->createMock(
            BatchResponseFactory::class
        );
        $this->batchResponseMock = $this->createMock(
            BatchResponse::class
        );
        $this->loggerHelperMock = $this->createMock(
            LoggerHelper::class
        );
        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);

        $this->cartLocationId = new CartLocationId(
            $this->cartIntegrationRepositoryMock,
            $this->requestCommandFactoryMock,
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders
        );
    }

    public function testResolveReturnCartLocationId()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $resolveRequestMock->expects($this->any())
            ->method('getValue')
            ->willReturn(['model' => new class {
                public function getId() { return 1; }
            }]);

        $this->cartIntegrationInterface->expects($this->once())->method('getLocationId')->willReturn('5541');

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')->willReturn($this->cartIntegrationInterface);

        $batchResponse = $this->cartLocationId->resolve($this->contextMock, $this->fieldMock, $requests);

        // Assertions
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    public function testResolveReturnCartLocationIdNull()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [$resolveRequestMock];

        $resolveRequestMock->expects($this->any())
            ->method('getValue')
            ->willReturn(['model' => new class {
                public function getId() { return 1; }
            }]);

        $this->cartIntegrationInterface->expects($this->once())->method('getLocationId')->willReturn('5541');

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')->willReturn($this->cartIntegrationInterface);

        $batchResponse = $this->cartLocationId->resolve($this->contextMock, $this->fieldMock, $requests);

        // Assertions
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }
}
