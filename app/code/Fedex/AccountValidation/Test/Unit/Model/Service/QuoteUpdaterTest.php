<?php

declare(strict_types=1);

namespace Fedex\AccountValidation\Test\Unit\Model\Service;

use Fedex\AccountValidation\Model\Service\QuoteUpdater;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\CartFactory;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class QuoteUpdaterTest extends TestCase
{
    /** @var CartFactory&MockObject */
    private $cartFactory;

    /** @var Cart&MockObject */
    private $cart;

    /** @var Quote&MockObject */
    private $quote;

    private QuoteUpdater $quoteUpdater;

    protected function setUp(): void
    {
        $this->cartFactory = $this->createMock(CartFactory::class);
        $this->cart = $this->createMock(Cart::class);
        $this->quote = $this->createMock(Quote::class);

        $this->cartFactory->method('create')->willReturn($this->cart);
        $this->cart->method('getQuote')->willReturn($this->quote);

        $this->quoteUpdater = new QuoteUpdater($this->cartFactory);
    }

    public function testUpdateWithValidAccountNumber(): void
    {
        $accountNumber = '123456';

        $this->quote->expects($this->once())
            ->method('setData')
            ->with('fedex_ship_account_number', $accountNumber)
            ->willReturnSelf();

        $this->quote->expects($this->once())->method('save');

        $this->quoteUpdater->update($accountNumber, true);
    }

    public function testUpdateWithInvalidAccountNumber(): void
    {
        $accountNumber = '123456';

        $this->quote->expects($this->once())
            ->method('setData')
            ->with('fedex_ship_account_number', null)
            ->willReturnSelf();

        $this->quote->expects($this->once())->method('save');

        $this->quoteUpdater->update($accountNumber, false);
    }
}
