<?php

declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use PHPUnit\Framework\TestCase;
use Fedex\Cart\Model\Quote\Product\Add as QuoteProductAdd;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;

class AddToCartContextTest extends TestCase
{
    /**
     * @var AddToCartContext
     */
    private $addToCartContext;

    protected function setUp(): void
    {
        $this->addToCartContext = new AddToCartContext(
            $this->createMock(RequestInterface::class),
            $this->createMock(QuoteProductAdd::class),
            $this->createMock(ProductRepositoryInterface::class),
            $this->createMock(RedirectFactory::class),
            $this->createMock(RedirectInterface::class),
            $this->createMock(Session::class)
        );
    }

    public function testGetRequestInterface(): void
    {
        $this->assertInstanceOf(RequestInterface::class, $this->addToCartContext->getRequestInterface());
    }

    public function testGetQuoteProductAdd(): void
    {
        $this->assertInstanceOf(QuoteProductAdd::class, $this->addToCartContext->getQuoteProductAdd());
    }

    public function testGetProductRepositoryInterface(): void
    {
        $this->assertInstanceOf(
            ProductRepositoryInterface::class,
            $this->addToCartContext->getProductRepositoryInterface()
        );
    }

    public function testGetRedirectFactory(): void
    {
        $this->assertInstanceOf(RedirectFactory::class, $this->addToCartContext->getRedirectFactory());
    }

    public function testGetRedirectInterface(): void
    {
        $this->assertInstanceOf(RedirectInterface::class, $this->addToCartContext->getRedirectInterface());
    }

    public function testGetSession(): void
    {
        $this->assertInstanceOf(Session::class, $this->addToCartContext->getSession());
    }
}
