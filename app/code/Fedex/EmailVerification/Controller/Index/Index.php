<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Controller\Index;

use Fedex\EmailVerification\Model\EmailVerification;
use Fedex\SelfReg\Block\Landing;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\UrlInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;

class Index implements ActionInterface
{
    /**
     * Constructor
     *
     * @param EmailVerification $emailVerification
     * @param Landing $selfRegLanding
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param Http $request
     * @param UrlInterface $url
     * @param StoreRepositoryInterface $storeRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        protected EmailVerification $emailVerification,
        protected Landing $selfRegLanding,
        protected Context $context,
        protected ScopeConfigInterface $scopeConfig,
        protected Http $request,
        protected UrlInterface $url,
        private StoreRepositoryInterface $storeRepository,
        private StoreManagerInterface $storeManager
    )
    {
    }

    /**
     * Execute action
     *
     * @return void
     */
    public function execute()
    {
        $redirectUrl = '';
        $params = $this->context->getRequest()->getParams();
        $storeId = $this->scopeConfig->getValue("ondemand_setting/category_setting/b2b_default_store");
        $store = $this->storeRepository->get($storeId);
        $this->storeManager->setCurrentStore($store->getId());

        if (isset($params['key'])) {
            $emailVerificationCustomer = $this->emailVerification->getCustomerByEmailUuid($params['key']);
            $isVerificationLinkActive = $this->emailVerification->isVerificationLinkActive($emailVerificationCustomer);
            if ($isVerificationLinkActive) {
                $isStatusChanged = $this->emailVerification->changeCustomerStatus($emailVerificationCustomer);
                $redirectUrl = $isStatusChanged ? $this->selfRegLanding->getLoginUrl() :
                    $this->url->getUrl('oauth/fail/');
            } else {
                $this->emailVerification->setExpiredLinkErrorMessage($emailVerificationCustomer);
                $redirectUrl = $this->url->getUrl('emailverification/fail/');
            }
        } else {
            $redirectUrl = $this->url->getUrl('oauth/fail/');
        }

        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->context->getResultRedirectFactory()->create();
        $resultRedirect->setUrl($redirectUrl);

        return $resultRedirect;
    }
}
