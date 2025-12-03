<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplaceProduct
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplaceProduct\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

class Context
{
    /**
     * @param LoggerInterface $logger
     * @param ManagerInterface $messageManager
     * @param RequestInterface $request
     * @param RedirectFactory $resultRedirectFactory
     * @param RedirectInterface $redirectInterface
     * @param Session $session
     */
    public function __construct(
        private LoggerInterface $logger,
        private ManagerInterface $messageManager,
        private RequestInterface $request,
        private RedirectFactory $resultRedirectFactory,
        private RedirectInterface $redirectInterface,
        private Session $session,
    ) {
    }

    /**
     * Get Logger Interface
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get Message Manager Interface
     *
     * @return ManagerInterface
     */
    public function getManagerInterface()
    {
        return $this->messageManager;
    }

    /**
     * Get Request Interface
     *
     * @return RequestInterface
     */
    public function getRequestInterface()
    {
        return $this->request;
    }

    /**
     * Get Redirect Factory
     *
     * @return RedirectFactory
     */
    public function getRedirectFactory()
    {
        return $this->resultRedirectFactory;
    }

    /**
     * Get Redirect Interface
     *
     * @return RedirectInterface
     */
    public function getRedirectInterface()
    {
        return $this->redirectInterface;
    }

    /**
     * Get Session
     *
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }
}
