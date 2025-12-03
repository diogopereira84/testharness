<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Test\Model\Xml\PunchoutBuilder\Request;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request\XmlContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;
use Magento\Framework\UrlInterface;
use PHPUnit\Framework\TestCase;

class XmlContextTest extends TestCase
{
    /** @var XmlContext  */
    private XmlContext $context;

    /**
     * @return void
     */
    public function setUp(): void
    {
        $xmlFactory = $this->createStub(ElementFactory::class);
        $cookieReader = $this->createStub(CookieReaderInterface::class);
        $customerSession = $this->createStub(CustomerSession::class);
        $config = $this->createStub(MarketplaceConfig::class);
        $request = $this->createStub(RequestInterface::class);
        $urlBuilder = $this->createStub(UrlInterface::class);

        $this->context = new XmlContext(
            $xmlFactory,
            $cookieReader,
            $customerSession,
            $config,
            $request,
            $urlBuilder
        );
    }

    /**
     * @return void
     */
    public function testGetXmlFactory(): void
    {
        $this->assertInstanceOf(ElementFactory::class, $this->context->getElementFactory());
    }

    /**
     * @return void
     */
    public function testGetCookieReaderInterface(): void
    {
        $this->assertInstanceOf(CookieReaderInterface::class, $this->context->getCookieReaderInterface());
    }

    /**
     * @return void
     */
    public function testGetCustomerSession(): void
    {
        $this->assertInstanceOf(CustomerSession::class, $this->context->getCustomerSession());
    }

    /**
     * @return void
     */
    public function testGetMarketplaceConfig(): void
    {
        $this->assertInstanceOf(MarketplaceConfig::class, $this->context->getMarketplaceConfig());
    }

    /**
     * @return void
     */
    public function testGetRequest(): void
    {
        $this->assertInstanceOf(RequestInterface::class, $this->context->getRequestInterface());
    }

    /**
     * @return void
     */
    public function testGetUrlInterface(): void
    {
        $this->assertInstanceOf(UrlInterface::class, $this->context->getUrlInterface());
    }
}
