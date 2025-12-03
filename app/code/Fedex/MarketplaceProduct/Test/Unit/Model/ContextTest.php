<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Test\Unit\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Fedex\MarketplaceProduct\Model\Context;

class ContextTest extends TestCase
{
    /**
     * @var Context
     */
    private Context $context;

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $messageManager = $this->createMock(ManagerInterface::class);
        $request = $this->createMock(RequestInterface::class);
        $resultRedirectFactory = $this->createMock(RedirectFactory::class);
        $redirectInterface = $this->createMock(RedirectInterface::class);
        $session = $this->createMock(Session::class);

        $this->context = new Context(
            $logger,
            $messageManager,
            $request,
            $resultRedirectFactory,
            $redirectInterface,
            $session
        );
    }

    /**
     * Test getLogger method.
     *
     * @return void
     */
    public function testGetLogger(): void
    {
        $logger = $this->context->getLogger();

        $this->assertInstanceOf(LoggerInterface::class, $logger);
    }

    /**
     * Test getManagerInterface method.
     *
     * @return void
     */
    public function testGetManagerInterface(): void
    {
        $messageManager = $this->context->getManagerInterface();

        $this->assertInstanceOf(ManagerInterface::class, $messageManager);
    }

    /**
     * Test getRequestInterface method.
     *
     * @return void
     */
    public function testGetRequestInterface(): void
    {
        $request = $this->context->getRequestInterface();

        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    /**
     * Test getRedirectFactory method.
     *
     * @return void
     */
    public function testGetRedirectFactory(): void
    {
        $redirectFactory = $this->context->getRedirectFactory();

        $this->assertInstanceOf(RedirectFactory::class, $redirectFactory);
    }

    /**
     * Test getRedirectInterface method.
     *
     * @return void
     */
    public function testGetRedirectInterface(): void
    {
        $redirectInterface = $this->context->getRedirectInterface();

        $this->assertInstanceOf(RedirectInterface::class, $redirectInterface);
    }

    /**
     * Test getSession method.
     *
     * @return void
     */
    public function testGetSession(): void
    {
        $session = $this->context->getSession();

        $this->assertInstanceOf(Session::class, $session);
    }
}
