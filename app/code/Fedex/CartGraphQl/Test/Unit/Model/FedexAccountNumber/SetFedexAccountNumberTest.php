<?php

/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Athira Indrakumar <aindrakumar@mcfadyen.com>
 */

declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\FedexAccountNumber;

use Fedex\CartGraphQl\Exception\GraphQlFujitsuResponseException;
use Fedex\Cart\Helper\Data as CartDataHelper;
use Fedex\CartGraphQl\Model\FedexAccountNumber\SetFedexAccountNumber;
use PHPUnit\Framework\TestCase;
use Fedex\CartGraphQl\Model\Checkout\Cart;

/**
 * @inheritdoc
 */
class SetFedexAccountNumberTest extends TestCase
{
    /**
     * @var CartDataHelper
     */
    protected CartDataHelper $cartHelperMock;

    /**
     * @var Cart
     */
    protected Cart $quoteMock;

    /**
     * @var array
     */
    protected array $data;

    /**
     * @var SetFedexAccountNumber
     */
    protected SetFedexAccountNumber $setFedexAccountNumberObject;

    /**
     * Set up the test environment
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->cartHelperMock = $this->getMockBuilder(CartDataHelper::class)
            ->onlyMethods(['isAddressClassificationFixToggleEnabled', 'encryptData'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->setFedexAccountNumberObject = new SetFedexAccountNumber(
            $this->cartHelperMock
        );
    }

    /**
     * Test case for SetFedexAccountNumber functionality
     *
     * @return void
     */
    public function testSetFedexAccountNumberIf(): void
    {
        $fedexAccountNumber = '123456';
        $fedexShipNumber = '654321';
        $quoteMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMock();
        $quoteMock->expects($this->any())
            ->method('setData')
            ->willReturnSelf();
        $this->cartHelperMock->expects($this->once())
            ->method('encryptData')
            ->with($fedexAccountNumber)
            ->willReturn($fedexAccountNumber);
        $this->setFedexAccountNumberObject->setFedexAccountNumber(
            $fedexAccountNumber,
            $fedexShipNumber,
            $quoteMock
        );
    }

    /**
     * Test exception handling in setFedexAccountNumber method
     *
     * @return void
     */
    public function testSetFedexAccountNumberException(): void
    {
        $fedexAccountNumber = '123456';
        $fedexShipNumber = '654321';
        $exceptionMessage = 'Test exception message';

        $quoteMock = $this->getMockBuilder(Cart::class)
            ->disableOriginalConstructor()
            ->addMethods(['setData'])
            ->getMock();

        $quoteMock->expects($this->once())
            ->method('setData')
            ->willThrowException(new \Exception($exceptionMessage));

        $this->expectException(GraphQlFujitsuResponseException::class);
        $this->expectExceptionMessage($exceptionMessage);

        $this->setFedexAccountNumberObject->setFedexAccountNumber(
            $fedexAccountNumber,
            $fedexShipNumber,
            $quoteMock
        );
    }
}
