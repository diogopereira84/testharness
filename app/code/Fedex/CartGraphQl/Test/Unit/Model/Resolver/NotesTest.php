<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Yash Rajeshbhai Solanki <yash.solanki.osv@fedex.com>
 */

namespace Fedex\CartGraphQl\Test\Unit\Model\Resolver;

use Fedex\GraphQl\Model\GraphQlBatchRequestCommandFactory as RequestCommandFactory;
use Fedex\InStoreConfigurations\Api\ConfigInterface as InstoreConfig;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponseFactory;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Fedex\GraphQl\Model\Validation\ValidationBatchComposite;
use Magento\Framework\GraphQl\Query\Resolver\ResolveRequest;
use Magento\Framework\GraphQl\Query\Resolver\BatchResponse;
use Magento\GraphQl\Model\Query\ContextExtensionInterface;
use Fedex\CartGraphQl\Model\Note\Command\SaveInterface;
use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Api\CartRepositoryInterface;
use Fedex\CartGraphQl\Model\Resolver\Notes;
use PHPUnit\Framework\MockObject\Exception;
use Magento\Store\Api\Data\StoreInterface;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use Fedex\CartGraphQl\Helper\LoggerHelper;
use Magento\GraphQl\Model\Query\Context;
use Fedex\FXOPricing\Model\FXORateQuote;
use Fedex\GraphQl\Model\NewRelicHeaders;
use PHPUnit\Framework\TestCase;
use Magento\Quote\Model\Quote;
use Magento\Framework\Phrase;

class NotesTest extends TestCase
{
    /**
     * @var (\Fedex\GraphQl\Model\NewRelicHeaders & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $newRelicHeaders;

    /**
     * @var (\Magento\Framework\Serialize\Serializer\Json & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $jsonSerializer;

    /**
     * @var (\Fedex\CartGraphQl\Model\Note\Command\SaveInterface & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $commandOrderNotesSaveMock;

    /**
     * @var (\Fedex\CartGraphQl\Model\Checkout\Cart & \PHPUnit\Framework\MockObject\MockObject)
     */
    protected $storeMock;

    /**
     * @var ContextExtensionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextExtensionMock;

    /**
     * @var Context|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $context;

    /**
     * @var BatchResponseFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $batchResponseMockFactory;

    /**
     * @var BatchResponse|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $batchResponseMock;

    /**
     * @var ContextInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $contextMock;

    /**
     * @var Field|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $fieldMock;

    /**
     * @var Notes
     */
    private $resolver;

    /**
     * @var CartRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartRepositoryMock;

    /**
     * @var InstoreConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $instoreConfigMock;

    /**
     * @var FXORateQuote|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fxoRateQuoteMock;

    /**
     * @var SaveInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $addressMock;

    /**
     * @var RequestCommandFactory|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestCommandFactoryMock;

    /**
     * @var ValidationBatchComposite|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validationCompositeMock;

    /**
     * @var Cart|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cartModelMock;

    /**
     * @var LoggerHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loggerHelperMock;

    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        $this->newRelicHeaders = $this->createMock(NewRelicHeaders::class);
        $this->cartRepositoryMock = $this->createMock(CartRepositoryInterface::class);
        $this->instoreConfigMock = $this->createMock(InstoreConfig::class);
        $this->fxoRateQuoteMock = $this->createMock(FXORateQuote::class);
        $this->jsonSerializer = $this->createMock(Json::class);
        $this->loggerHelperMock = $this->createMock(LoggerHelper::class);
        $this->cartModelMock = $this->createMock(Cart::class);
        $this->addressMock = $this->createMock(Quote\Address::class);
        $this->requestCommandFactoryMock = $this->createMock(RequestCommandFactory::class);
        $this->commandOrderNotesSaveMock = $this->createMock(SaveInterface::class);
        $this->validationCompositeMock = $this->createMock(ValidationBatchComposite::class);
        $this->storeMock = $this->getMockBuilder(StoreInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeMock->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->contextExtensionMock = $this->getMockBuilder(ContextExtensionInterface::class)
            ->onlyMethods(['getStore'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->contextExtensionMock->expects($this->any())
            ->method('getStore')
            ->willReturn($this->storeMock);
        $this->context->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($this->contextExtensionMock);
        $this->batchResponseMockFactory = $this->createMock(
            BatchResponseFactory::class
        );
        $this->batchResponseMock = $this->createMock(
            BatchResponse::class
        );
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->batchResponseMockFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->batchResponseMock);
        $this->resolver = new Notes(
            $this->cartRepositoryMock,
            $this->fxoRateQuoteMock,
            $this->instoreConfigMock,
            $this->commandOrderNotesSaveMock,
            $this->jsonSerializer,
            $this->cartModelMock,
            $this->requestCommandFactoryMock,
            $this->batchResponseMockFactory,
            $this->loggerHelperMock,
            $this->validationCompositeMock,
            $this->newRelicHeaders,
            [],
        );
    }

    /**
     * @throws Exception
     * @throws GraphQlInputException
     */
    public function testResolveAddsNotesToCart()
    {
        $inputData = [
            'input' => [
                'cart_id' => 'cart_id_value',
                'notes' => [
                    'text' => 'note_text_value',
                    'audit' => [
                        'creationTime' => '2023-04-28T10:30:00Z',
                        'user' => 'Ange2',
                        'userReference' => [
                            'reference' => 'Testing',
                            'source' => 'MAGENTO'
                        ]
                    ]
                ]
            ]
        ];

        $this->instoreConfigMock->expects($this->any())
            ->method('isEnabledAddNotes')
            ->willReturn(true);
        $this->jsonSerializer->expects($this->any())
            ->method('serialize')
            ->willReturn(json_encode($inputData));

        $cartMock = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['setOrderNotes'])
            ->onlyMethods(['isSaveAllowed'])
            ->getMockForAbstractClass();
        $cartMock->expects($this->any())
            ->method('setOrderNotes')
            ->willReturn(json_encode($inputData['input']['notes']));
        $this->cartRepositoryMock->expects($this->any())
            ->method('get')
            ->willReturn($cartMock);
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requests = [$resolveRequestMock];
        $this->cartModelMock->method('getCart')->willReturn($cartMock);
        $resolveRequestMock->expects($this->any())
            ->method('getArgs')
            ->willReturn($inputData);

        $this->cartModelMock->method('setContactInfo')->willReturnSelf();
        $batchResponse = $this->resolver->proceed(
            $this->contextMock,
            $this->fieldMock,
            $requests,
            []
        );
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    /**
     * Tests that the resolve method throws an exception when notes are disabled.
     *
     * @return void
     */
    public function testResolveThrowsExceptionWhenNotesDisabled()
    {
        $this->instoreConfigMock->expects($this->any())
            ->method('isEnabledAddNotes')
            ->willReturn(false);
        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requests = [$resolveRequestMock];
        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Add notes is not enabled.');

        $batchResponse = $this->resolver->proceed(
            $this->contextMock,
            $this->fieldMock,
            $requests,
            []
        );
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    /**
     * Tests that the proceed method correctly logs a FujitsuException.
     */
    public function testProceedLogsFujitsuException()
    {
        $this->instoreConfigMock->expects($this->once())
            ->method('isEnabledAddNotes')
            ->willReturn(true);

        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requests = [$resolveRequestMock];

        $inputData = [
            'input' => [
                'cart_id' => 'cart_id_value',
                'notes' => ['text' => 'note_text_value']
            ]
        ];
        $resolveRequestMock->method('getArgs')->willReturn($inputData);

        $cartMock = $this->createMock(Quote::class);
        $this->cartModelMock->method('getCart')->willReturn($cartMock);

        $this->jsonSerializer->method('serialize')->willReturn(json_encode($inputData['input']['notes']));

        $fujitsuException = new GraphQlFujitsuResponseException(new Phrase('Fujitsu error!'));

        $this->fxoRateQuoteMock->method('getFXORateQuote')->willThrowException($fujitsuException);

        $this->loggerHelperMock->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Error on getting FXO Rate Quote. Fujitsu error!'), []);

        $this->commandOrderNotesSaveMock->expects($this->once())
            ->method('execute')
            ->with($cartMock, json_encode($inputData['input']['notes']));

        $batchResponse = $this->resolver->proceed(
            $this->contextMock,
            $this->fieldMock,
            $requests,
            []
        );
        $this->assertInstanceOf(BatchResponse::class, $batchResponse);
    }

    /**
     * Tests that the proceed method logs and throws an exception when a generic exception occurs.
     *
     * @return void
     */
    public function testProceedLogsAndThrowsOnGenericException()
    {
        $this->instoreConfigMock->expects($this->once())
            ->method('isEnabledAddNotes')
            ->willReturn(true);

        $resolveRequestMock = $this->createMock(ResolveRequest::class);
        $requests = [$resolveRequestMock];

        $resolveRequestMock->method('getArgs')->willReturn([
            'input' => [
                'cart_id' => 'cart_id_value',
                'notes' => ['text' => 'note_text_value']
            ]
        ]);

        $exception = new \Exception('Generic error!');
        $this->cartModelMock->method('getCart')->willThrowException($exception);

        $this->loggerHelperMock->expects($this->atLeastOnce())
            ->method('error')
            ->withConsecutive(
                [$this->stringContains('Error on saving information. Generic error!'), []],
                [$this->stringContains($exception->getTraceAsString()), []],
                [$this->stringContains('GTN:'), []]
            );

        $this->expectException(GraphQlInputException::class);
        $this->expectExceptionMessage('Error on saving information Generic error!');

        $this->resolver->proceed(
            $this->contextMock,
            $this->fieldMock,
            $requests,
            []
        );
    }
}
