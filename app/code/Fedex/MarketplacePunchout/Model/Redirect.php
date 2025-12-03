<?php
/**
 * @category    Fedex
 * @package     Fedex_MarketplacePunchout
 * @copyright   Copyright (c) 2023 FedEx
 * @author      Nathan Alves <nathan.alves.osv@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\MarketplacePunchout\Model;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Response\RedirectInterface as ResponseRedirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Backend\Model\View\Result\Redirect as ResultRedirect;
use Magento\Framework\UrlInterface;

class Redirect
{
    /**
     * Constructor
     *
     * @param RedirectFactory $resultRedirectFactory
     * @param ResponseRedirect $responseRedirect
     * @param Session $customerSession
     */
    public function __construct(
        private RedirectFactory $resultRedirectFactory,
        private ResponseRedirect $responseRedirect,
        private Session $customerSession,
        private UrlInterface $urlBuilder
    ) {
    }

    /**
     * Redirects the user to marketplace.
     *
     * @param bool $toMarketplace
     * @param string|null $url
     * @param bool $error
     * @return ResultRedirect
     */
    public function redirect(bool $toMarketplace = false, string $url = null, bool $error = false): ResultRedirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        if ($toMarketplace) {
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        if ($error) {
            $url = $this->urlBuilder->getUrl(
                $this->responseRedirect->getRefererUrl().'?mktsellererror=1',
                []
            );
            $resultRedirect->setUrl($url);
            return $resultRedirect;
        }

        $resultRedirect->setUrl($this->responseRedirect->getRefererUrl());
        return $resultRedirect;
    }
}
