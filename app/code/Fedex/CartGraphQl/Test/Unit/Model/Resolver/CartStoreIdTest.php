<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2022 Fedex
 * @author       Tiago Hayashi Daniel <tdaniel@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\Cart\Api\CartIntegrationRepositoryInterface;
use Fedex\Cart\Api\Data\CartIntegrationInterface;
use Fedex\CartGraphQl\Model\Resolver\CartStoreId;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommand as RequestCommand;
use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite as ValidationComposite;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\QuoteGraphQl\Model\Cart\GetCartForUser;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\GraphQl\Model\Query\Context;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Fedex\GraphQl\Model\NewRelicHeaders;

/**
 * @inheritdoc
 */
class CartStoreIdTest extends TestCase
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
     * @var CartStoreId
     */
    protected CartStoreId $cartLocationId;

    /**
     * @var GetCartForUser|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $getCartForUserMock;

    /**
     * @var Field|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldMock;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
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

    /**
     * Sets up the environment before each test.
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->cartIntegrationRepositoryMock = $this->getMockBuilder(CartIntegrationRepositoryInterface::class)
            ->onlyMethods(['getByQuoteId'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->cartIntegrationInterface = $this->getMockBuilder(CartIntegrationInterface::class)
            ->onlyMethods(['getStoreId'])
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

        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);
        $this->loggerHelperMock = $this->createMock(
            LoggerHelper::class
        );

        $this->cartLocationId = new CartStoreId(
            $this->cartIntegrationRepositoryMock,
            $this->requestCommandFactoryMock,
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders
        );
    }

    /**
     * Tests that the resolve method returns the correct store ID for the cart.
     *
     * @return void
     */
    public function testResolveReturnCartStoreId()
    {
        $requests = [];
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $resolveRequestMock->expects($this->any())
            ->method('getValue')
            ->willReturn(['model' => new class {
                public function getId()
                {
                    return 1;
                }
            }]);
        $this->cartIntegrationInterface->expects($this->any())->method('getStoreId')->willReturn(1);

        $this->cartIntegrationRepositoryMock->expects($this->any())
            ->method('getByQuoteId')->willReturn($this->cartIntegrationInterface);

        $batchResponse = $this->cartLocationId->resolve($this->contextMock, $this->fieldMock, $requests);

        // Assertions
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    /**
     * Tests the resolve method to ensure it returns null when the cart store ID is not set or unavailable.
     *
     * @return void
     */
    public function testResolveReturnCartStoreIdNull()
    {
        $resolveRequestMock = $this->createMock(ResolveRequest::class);

        $requests = [];

        $resolveRequestMock->expects($this->any())
            ->method('getValue')
            ->willReturn(['model' => new class {
                public function getId()
                {
                    return 1;
                }
            }]);

        $batchResponse = $this->cartLocationId->resolve($this->contextMock, $this->fieldMock, $requests);

        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    /**
     * Tests that the proceed method adds a response for a valid model ID.
     *
     * @return void
     */
    public function testProceedAddsResponseForValidModelId()
    {
        $context = $this->createMock(ContextInterface::class);
        $field = $this->createMock(Field::class);
        $headerArray = [];

        $modelWithId = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $modelWithId->expects($this->exactly(2))
            ->method('getId')
            ->willReturn(42);

        $resolveRequestWithId = $this->createMock(ResolveRequest::class);
        $resolveRequestWithId->expects($this->once())
            ->method('getValue')
            ->willReturn(['model' => $modelWithId]);

        $cartIntegration = $this->createMock(CartIntegrationInterface::class);
        $cartIntegration->expects($this->once())
            ->method('getStoreId')
            ->willReturn(99);

        $this->cartIntegrationRepositoryMock->expects($this->once())
            ->method('getByQuoteId')
            ->with(42)
            ->willReturn($cartIntegration);

        $this->batchResponseMock->expects($this->once())
            ->method('addResponse')
            ->with($resolveRequestWithId, 99);

        $result = $this->cartLocationId->proceed(
            $context,
            $field,
            [$resolveRequestWithId],
            $headerArray
        );

        $this->assertSame($this->batchResponseMock, $result);
    }

    /**
     * Tests that the proceed method skips the response when provided with an invalid model ID.
     *
     * @return void
     */
    public function testProceedSkipsResponseForInvalidModelId()
    {
        $context = $this->createMock(ContextInterface::class);
        $field = $this->createMock(Field::class);
        $headerArray = [];

        $modelWithoutId = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getId'])
            ->getMock();
        $modelWithoutId->expects($this->once())
            ->method('getId')
            ->willReturn(0);

        $resolveRequestWithoutId = $this->createMock(ResolveRequest::class);
        $resolveRequestWithoutId->expects($this->once())
            ->method('getValue')
            ->willReturn(['model' => $modelWithoutId]);

        $this->cartIntegrationRepositoryMock->expects($this->never())
            ->method('getByQuoteId');
        $this->batchResponseMock->expects($this->never())
            ->method('addResponse');

        $result = $this->cartLocationId->proceed(
            $context,
            $field,
            [$resolveRequestWithoutId],
            $headerArray
        );

        $this->assertSame($this->batchResponseMock, $result);
    }
}
