<?php
/**
 * Copyright ©  FedEx All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\Login\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\UrlInterface;
use Psr\Log\LoggerInterface;
use Fedex\Login\Helper\Login;
use Magento\Framework\App\Request\Http;
use Fedex\FuseBiddingQuote\ViewModel\FuseBidViewModel;
use Fedex\EnvironmentManager\Model\Config\B212363OpenRedirectionMaliciousSiteFix;

class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Constructor
     * @param Context $context
     * @param UrlInterface $url
     * @param LoggerInterface $logger
     * @param Login $login
     * @param Http $request
     * @param FuseBidViewModel $fuseBidViewModel
     */
    public function __construct(
        Context $context,
        protected UrlInterface $url,
        protected LoggerInterface $logger,
        protected Login $login,
        private Http $request,
        protected FuseBidViewModel $fuseBidViewModel,
        readonly B212363OpenRedirectionMaliciousSiteFix $openRedirectionMaliciousSiteFix
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     * B-1320022 - WLGN integration for selfReg customer
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $redirectUrl = '';
        $params = $this->request->getParams();
        if($this->login->isWireMockLoginEnable() & isset($params['uuid']) && !empty($params['uuid'])) {
            $uuid = $params['uuid'];
            $this->login->setUuidCookie($uuid);
            $loginResponse = $this->login->handleCustomerSession($uuid);
        } else {
            $loginResponse = $this->login->handleCustomerSession();
        }

        if (isset($params['rc'])) {
            // B-2123653 Open redirect to malicious site
            if ($this->openRedirectionMaliciousSiteFix->isActive()) {
                $decodedUrl = base64_decode($params['rc']);
                $parsedUrl = parse_url($decodedUrl);
                // this allows only fedex.com domain and its subdomains for redirection
                // If need to allow other domains in the future, need to update this pattern or make it configurable.
                $allowedDomains = '/(^|\.)fedex\.com$/';
                if (isset($parsedUrl['host']) && preg_match($allowedDomains, $parsedUrl['host'])) {
                    $redirectUrl = $decodedUrl;
                } else {
                    $this->logger->warning("Invalid redirect attempt: " . $decodedUrl);
                    $redirectUrl = $this->login->getRetailStoreUrl();
                }
            } else {
                $redirectUrl = base64_decode($params['rc']);
                // if there is an error and retail login error code is not set
                // then redirects to on demand fail index
            }
            if (
                isset($loginResponse['status']) &&
                $loginResponse['status'] == "error" &&
                empty($loginResponse['code'])
            ) {
                $redirectUrl = $this->login->getOndemandStoreUrl();
            }
            if (!empty($this->login->getCompanyId()) && $this->login->getStoreCode() === 'default') {
                $redirectUrl = $this->login->getRedirectUrl();
            }
        } else {
            if (isset($loginResponse['status']) && $loginResponse['status'] == "success") {
                $redirectUrl = $this->login->getRedirectUrl();
            } else {
                if (!isset($loginResponse['status']) || !isset($loginResponse['code'])) {
                    $redirectUrl = $this->login->getRetailStoreUrl();
                } else {
                    if ($loginResponse['code'] == 'retail_login_error') {
                        $redirectUrl = $this->login->getRetailStoreUrl();
                    } else {
                        if ($this->login->isEmailVerificationRequired()) {
                            $this->login->sendUserVerificationEmail();
                        }
                        $redirectUrl = $this->url->getUrl('oauth/fail/');
                    }
                }
            }
        }
        if ($this->fuseBidViewModel->isFuseBidToggleEnabled()){
            $redirectUrl = $this->login->getFuseBidQuoteUrl($redirectUrl);
        }
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectUrl);

        return $resultRedirect;
    }
}
