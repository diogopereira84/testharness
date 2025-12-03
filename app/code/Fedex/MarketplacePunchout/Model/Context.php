<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Fedex\MarketplacePunchout\Model\Marketplace;
use Fedex\MarketplacePunchout\Model\Config\Marketplace as MarketplaceConfig;

class Context
{

    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param Marketplace $marketplace
     * @param ManagerInterface $messageManager
     * @param MarketplaceConfig $config
     * @param RequestInterface $request
     * @param CustomerSession $customerSession
     */
    public function __construct(
        private LoggerInterface $logger,
        private Marketplace $marketplace,
        private ManagerInterface $messageManager,
        private MarketplaceConfig $config,
        private RequestInterface $request,
        private CustomerSession $customerSession
    ) {
    }

    /**
     * Returns Logger Interface
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Returns Marketplace Model
     *
     * @return Marketplace
     */
    public function getMarketplace(): Marketplace
    {
        return $this->marketplace;
    }

    /**
     * Returns Message Manager Interface
     *
     * @return ManagerInterface
     */
    public function getMessageManagerInterface(): ManagerInterface
    {
        return $this->messageManager;
    }

    /**
     * Returns MarketplaceConfig Config
     *
     * @return MarketplaceConfig
     */
    public function getMarketplaceConfig(): MarketplaceConfig
    {
        return $this->config;
    }

    /**
     * Returns Request Interface
     *
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * Returns Customer Session
     *
     * @return CustomerSession
     */
    public function getCustomerSession(): CustomerSession
    {
        return $this->customerSession;
    }
}
