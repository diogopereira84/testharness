<?php
/**
 * @category    Fedex
 * @package     Fedex_FujitsuGateway
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Eduardo Oliveira
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Plugin;

use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\Note\Command\SaveInterface;
use Fedex\CartGraphQl\Model\Resolver\CreateOrUpdateOrder;
use Magento\Framework\GraphQl\Config\Element\Field;
use Fedex\CartGraphQl\Model\Checkout\Cart;
use PHPUnit\Framework\MockObject\MockObject;
use Fedex\CartGraphQl\Plugin\SetOrderNotesOnQuote;
use Magento\Quote\Model\Quote;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class SetOrderNotesOnQuoteTest extends TestCase
{
    /**
     * @var SetOrderNotesOnQuote Instance of the class being tested.
     */
    private SetOrderNotesOnQuote $instance;

    /**
     * Mock object for the SaveInterface used to simulate the behavior of the
     * order notes save command in unit tests.
     *
     * @var SaveInterface|MockObject
     */
    private SaveInterface|MockObject $commandOrderNotesSaveMock;

    /**
     * @var Cart|MockObject Mock object for the Cart model used in unit tests.
     */
    private Cart|MockObject $cartModelMock;

    /**
     * Mock object for the Quote class used in unit testing.
     *
     * @var Quote|MockObject
     */
    private Quote|MockObject $quoteMock;

    /**
     * @var Field|MockObject Mock object for the Field class used in unit testing.
     */
    private Field|MockObject $fieldMock;

    /**
     * @var CreateOrUpdateOrder|MockObject Mock object for the CreateOrUpdateOrder class
     */
    private CreateOrUpdateOrder|MockObject $createOrUpdateOrderMock;

    /**
     * @var ContextInterface|MockObject Mock object for the context interface used in unit tests.
     */
    private ContextInterface|MockObject $contextMock;

    /**
     * @var \Magento\Framework\GraphQl\Query\Resolver\Value|MockObject Mock object for the request used in unit tests.
     */
    private \Magento\Framework\GraphQl\Query\Resolver\Value|MockObject $requestMock;

    protected function setUp(): void
    {
        $this->commandOrderNotesSaveMock = $this->createMock(SaveInterface::class);
        $this->cartModelMock = $this->createMock(Cart::class);
        $this->instance = new SetOrderNotesOnQuote(
            $this->commandOrderNotesSaveMock,
            $this->cartModelMock
        );

        $this->quoteMock = $this->createMock(Quote::class);
        $this->fieldMock = $this->createMock(Field::class);
        $this->createOrUpdateOrderMock = $this->createMock(CreateOrUpdateOrder::class);
        $this->contextMock = $this->createMock(ContextInterface::class);
        $this->requestMock = $this->getMockBuilder(\Magento\Framework\GraphQl\Query\Resolver\Value::class)
            ->disableOriginalConstructor()
            ->addMethods(['getArgs'])
            ->getMock();
    }

    public function testAfterResolveWithValidNotes(): void
    {
        $resolveResult = [];
        $args = [
            "input" => [
                "notes" => [
                    "text" => "Sample Note",
                    "audit" => []
                ],
                "cart_id" => "1"
            ]
        ];

        $this->requestMock->expects($this->once())
            ->method('getArgs')
            ->willReturn($args);

        $this->cartModelMock->expects($this->once())
            ->method('getCart')
            ->with($args["input"]["cart_id"], $this->contextMock)
            ->willReturn($this->quoteMock);

        $this->commandOrderNotesSaveMock->expects($this->once())
            ->method('execute')
            ->with($this->quoteMock, json_encode($args["input"]["notes"]));

        $result = $this->instance->afterResolve(
            $this->createOrUpdateOrderMock,
            $resolveResult,
            $this->contextMock,
            $this->fieldMock,
            [$this->requestMock]
        );

        $this->assertSame($resolveResult, $result);
    }

    public function testAfterResolveWithEmptyNotes(): void
    {
        $resolveResult = [];
        $args = [
            "input" => [
                "notes" => null,
                "cart_id" => "1"
            ]
        ];

        $this->requestMock->expects($this->once())
            ->method('getArgs')
            ->willReturn($args);

        $this->cartModelMock->expects($this->never())
            ->method('getCart');

        $this->commandOrderNotesSaveMock->expects($this->never())
            ->method('execute');

        $result = $this->instance->afterResolve(
            $this->createOrUpdateOrderMock,
            $resolveResult,
            $this->contextMock,
            $this->fieldMock,
            [$this->requestMock]
        );

        $this->assertSame($resolveResult, $result);
    }

    public function testAfterResolveWithNoRequests(): void
    {
        $resolveResult = [];

        $this->cartModelMock->expects($this->never())
            ->method('getCart');

        $this->commandOrderNotesSaveMock->expects($this->never())
            ->method('execute');

        $result = $this->instance->afterResolve(
            $this->createOrUpdateOrderMock,
            $resolveResult,
            $this->contextMock,
            $this->fieldMock,
            []
        );

        $this->assertSame($resolveResult, $result);
    }
}
