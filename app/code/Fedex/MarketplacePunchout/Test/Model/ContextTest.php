<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 Fedex
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model;

use Fedex\MarketplacePunchout\Model\Marketplace;
use PHPUnit\Framework\TestCase;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Context;

class ContextTest extends TestCase
{
    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var Marketplace
     */
    private Marketplace $marketplace;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var MarketplaceConfig
     */
    private MarketplaceConfig $config;

    /**
     * @var RequestInterface
     */
    private RequestInterface $request;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customer;

    /**
     * @var Context
     */
    private Context $context;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->marketplace = $this->createStub(Marketplace::class);
        $this->messageManager = $this->createStub(ManagerInterface::class);
        $this->config = $this->createStub(MarketplaceConfig::class);
        $this->request = $this->createStub(RequestInterface::class);
        $this->customer = $this->createStub(CustomerSession::class);

        $this->context = new Context(
            $this->logger,
            $this->marketplace,
            $this->messageManager,
            $this->config,
            $this->request,
            $this->customer
        );
    }

    /**
     * @return void
     */
    public function testGetLogger()
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->context->getLogger());
    }

    /**
     * @return void
     */
    public function testMarketplace()
    {
        $this->assertInstanceOf(Marketplace::class, $this->context->getMarketplace());
    }

    /**
     * @return void
     */
    public function testGetMessageManagerInterface()
    {
        $this->assertInstanceOf(ManagerInterface::class, $this->context->getMessageManagerInterface());
    }

    /**
     * @return void
     */
    public function testGetMarketplaceConfig()
    {
        $this->assertInstanceOf(MarketplaceConfig::class, $this->context->getMarketplaceConfig());
    }

    /**
     * @return void
     */
    public function testGetRequest()
    {
        $this->assertInstanceOf(RequestInterface::class, $this->context->getRequest());
    }

    /**
     * @return void
     */
    public function testGetCustomerSession()
    {
        $this->assertInstanceOf(CustomerSession::class, $this->context->getCustomerSession());
    }

}
