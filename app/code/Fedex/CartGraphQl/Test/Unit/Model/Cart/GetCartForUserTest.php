<?php
/**
 * @category     Fedex
 * @package      Fedex_CartGraphQl
 * @copyright    Copyright (c) 2024 Fedex
 * @author       Eduardo Diogo Dias <eduardodias.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\CartGraphQl\Test\Unit\Model\Cart;

use Fedex\CartGraphQl\Model\Cart\GetCartForUser;
use Fedex\InStoreConfigurations\Api\ConfigInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteIdInterface;
use Magento\Quote\Model\Quote;
use Magento\QuoteGraphQl\Model\Cart\IsActive;
use Magento\QuoteGraphQl\Model\Cart\UpdateCartCurrency;
use PHPUnit\Framework\TestCase;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;

class GetCartForUserTest extends TestCase
{
    private $maskedQuoteIdToQuoteId;
    private $cartRepository;
    private $isActive;
    private $updateCartCurrency;
    private $config;
    private $quote;
    private $getCartForUser;

    protected function setUp(): void
    {
        $this->maskedQuoteIdToQuoteId = $this->createMock(MaskedQuoteIdToQuoteIdInterface::class);
        $this->cartRepository = $this->createMock(CartRepositoryInterface::class);
        $this->isActive = $this->createMock(IsActive::class);
        $this->updateCartCurrency = $this->createMock(UpdateCartCurrency::class);
        $this->config = $this->getMockBuilder(ConfigInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->quote = $this->getMockBuilder(Quote::class)
            ->disableOriginalConstructor()
            ->addMethods(['getCustomerId'])
            ->getMock();

        $this->getCartForUser = new GetCartForUser(
            $this->maskedQuoteIdToQuoteId,
            $this->cartRepository,
            $this->isActive,
            $this->updateCartCurrency,
            $this->config
        );
    }

    public function testExecuteWithFixDisabled(): void
    {
        $cartHash = 'sample_cart_hash';
        $cartId = 123;
        $customerId = null;
        $storeId = 1;
        $this->config
            ->method('isEnabledUserCannotPerformCartOperationsFix')
            ->willReturn(true);
        $this->maskedQuoteIdToQuoteId
            ->method('execute')
            ->with($cartHash)
            ->willReturn($cartId);

        $this->cartRepository
            ->method('get')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->isActive
            ->method('execute')
            ->with($this->quote)
            ->willReturn(true);

        $this->updateCartCurrency
            ->method('execute')
            ->with($this->quote, $storeId)
            ->willReturn($this->quote);

        $this->assertSame(
            $this->quote,
            $this->getCartForUser->execute($cartHash, $customerId, $storeId)
        );
    }

    public function testExecuteWithAuthorizationException(): void
    {
        $cartHash = 'sample_cart_hash';
        $cartId = 123;
        $customerId = 5;
        $storeId = 1;

        $this->maskedQuoteIdToQuoteId
            ->method('execute')
            ->with($cartHash)
            ->willReturn($cartId);

        $this->cartRepository
            ->method('get')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->isActive
            ->method('execute')
            ->with($this->quote)
            ->willReturn(true);

        $this->quote
            ->method('getCustomerId')
            ->willReturn(10);

        $this->expectException(GraphQlAuthorizationException::class);
        $this->getCartForUser->execute($cartHash, $customerId, $storeId);
    }

    public function testExecuteWithAuthorizationExceptionThrow(): void
    {
        $cartHash = 'sample_cart_hash';
        $cartId = 123;
        $customerId = 5;
        $storeId = 1;

        $this->config
            ->method('isEnabledUserCannotPerformCartOperationsFix')
            ->willReturn(true);
            
        $this->maskedQuoteIdToQuoteId
            ->method('execute')
            ->with($cartHash)
            ->willReturn($cartId);

        $this->cartRepository
            ->method('get')
            ->with($cartId)
            ->willReturn($this->quote);

        $this->isActive
            ->method('execute')
            ->with($this->quote)
            ->willReturn(true);

        $this->quote
            ->method('getCustomerId')
            ->willReturn(10);

        $this->getCartForUser->execute($cartHash, $customerId, $storeId);
    }
}
