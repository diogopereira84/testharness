<?php

/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Fedex\SubmitOrderSidebar\Block;

class CjOrderCookies extends \Magento\Framework\View\Element\Template
{
    /**
     * @param Context $context
     * @param RequestInterface $request
     * @param ToggleConfig $toggleConfig
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Data $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        public \Magento\Framework\App\RequestInterface $request,
        public \Fedex\EnvironmentManager\ViewModel\ToggleConfig $toggleConfig,
        private \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        private \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Function to Set Cookie
     *
     * @return CookieManager
     */
    public function setCustomCookie()
    {
        $cookieName = "cje";
        $domain = ".fedex.com";
        $urlData = $this->request->getParams();
        if (isset($urlData)) {
            $getLowerCase = array_change_key_case($urlData, CASE_LOWER);
            if (isset($getLowerCase["cjevent"])) {
                $cjevent = $getLowerCase["cjevent"];
            }
        }
        $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $publicCookieMetadata->setDurationOneYear();
        $publicCookieMetadata->setPath('/');
        $publicCookieMetadata->setDomain($domain);
        $publicCookieMetadata->setHttpOnly(false);

        return $this->cookieManager->setPublicCookie(
            $cookieName,
            $cjevent,
            $publicCookieMetadata
        );
    }

    /**
     * Function to get Cookie for CJ
     *
     * @return String
     */
    public function getCookie()
    {
        $cookieName = "cje";
        
        return $this->cookieManager->getCookie($cookieName);
    }
}
