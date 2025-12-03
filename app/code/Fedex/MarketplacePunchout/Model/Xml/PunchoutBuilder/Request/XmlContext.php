<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model\Xml\PunchoutBuilder\Request;

use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Simplexml\ElementFactory;
use Magento\Framework\Stdlib\Cookie\CookieReaderInterface;
use Magento\Framework\UrlInterface;

class XmlContext
{
    /**
     * @param ElementFactory $xmlFactory
     * @param CookieReaderInterface $cookieReader
     * @param CustomerSession $customerSession
     * @param MarketplaceConfig $config
     * @param RequestInterface $request
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        private ElementFactory $xmlFactory,
        private CookieReaderInterface $cookieReader,
        private CustomerSession $customerSession,
        private MarketplaceConfig $config,
        private RequestInterface $request,
        private UrlInterface $urlBuilder
    ) {
    }

    /**
     * Returns ElementFactory
     *
     * @return ElementFactory
     */
    public function getElementFactory(): ElementFactory
    {
        return $this->xmlFactory;
    }

    /**
     * Returns CookieReaderInterface
     *
     * @return CookieReaderInterface
     */
    public function getCookieReaderInterface(): CookieReaderInterface
    {
        return $this->cookieReader;
    }

    /**
     * Returns CustomerSession
     *
     * @return CustomerSession
     */
    public function getCustomerSession(): CustomerSession
    {
        return $this->customerSession;
    }

    /**
     * @return MarketplaceConfig
     */
    public function getMarketplaceConfig(): MarketplaceConfig
    {
        return $this->config;
    }

    /**
     * Returns RequestInterface
     *
     * @return RequestInterface
     */
    public function getRequestInterface(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Returns UrlInterface
     *
     * @return UrlInterface
     */
    public function getUrlInterface(): UrlInterface
    {
        return $this->urlBuilder;
    }
}
