<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Ondemand\Plugin\Cms\Noroute;

use Magento\Cms\Controller\Noroute\Index;
use Magento\Framework\Controller\Result\ForwardFactory;
use Magento\Framework\UrlInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Ondemand\Helper\Ondemand;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\Login\Helper\Login;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;       

class IndexPlugin
{
    public const QUOTE_NAME = "uploadtoquote_url_extension";

    public const EMAIL_REDIRECTION = 'mazegeeks_d226789_quote_link_redirection_from_email';

    /*
    * @var CookieManagerInterface
    */
    protected $_cookieManager;

    /**
    * @var CookieMetadataFactory
    */
    protected $_cookieMetadataFactory;

    /**
     * @param UrlInterface $resultForwardFactory
     * @param ToggleConfig $toggleConfig
     * @param Ondemand $onDemandHelper
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     */
    public function __construct(
        protected UrlInterface $urlInterface,
        protected ToggleConfig $toggleConfig,
        protected Ondemand $onDemandHelper,
        protected AuthHelper  $authHelper,
        protected Login  $loginHelper,
        protected Http  $http,
        protected CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected LoggerInterface $logger
    )
    {
    }

    /**
     * Render CMS 404 Not found page
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function aroundExecute(Index $subject, callable $proceed)
    {
        $currentUrl = $this->urlInterface->getCurrentUrl();
        //Below code for email quote link redirect if user not logged in
        $emailRedirection = $this->toggleConfig->getToggleConfigValue(self::EMAIL_REDIRECTION);
    
        if($emailRedirection) {
            if (!$this->authHelper->isLoggedIn() && str_contains($currentUrl, 'uploadtoquote/index/view')) {
                $this->setCookie($currentUrl);
                $parseUrl = parse_url($currentUrl);
                $parseUrl = explode('/',$parseUrl['path']);
                if(isset($parseUrl[2])) {
                    $urlExtention = $parseUrl[2];
                    if (!empty($this->onDemandHelper->getCompanyFromUrlExtension($urlExtention))) {
                        $redirectUrl = $this->urlInterface->getUrl('restructure/company/redirect/url/' . $urlExtention);
                        $subject->getResponse()->setRedirect($redirectUrl);
                        return;
                    } else {
                        return $proceed();
                    }
                }
            }
        }
       $commercialLandingPageFixToggle = $this->toggleConfig->getToggleConfigValue('explorers_d_197790_commercial_landing_page_fix');
        if ($commercialLandingPageFixToggle && strpos($currentUrl, 'selfreg/landing') !== false) {
            $parseUrl = parse_url($currentUrl);
            $parseUrl = explode('/',$parseUrl['path']);
            if(isset($parseUrl[2])) {
                $urlExtention = $parseUrl[2];
                if (!empty($this->onDemandHelper->getCompanyFromUrlExtension($urlExtention))) {
                    $redirectUrl = $this->urlInterface->getUrl('restructure/company/redirect/url/'.$urlExtention);
                    $subject->getResponse()->setRedirect($redirectUrl);
                } else {
                    return $proceed();
                }
            } else {
                return $proceed();
            }
        } else {
            return $proceed();
        }
    }

    /**
     * Set Email Link Cookie
     * @param string|null $currentUrl
     * @return void
     */
    public function setCookie($currentUrl = null)
    {
        $emailHitQuoteMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
        $emailHitQuoteMetadata->setDurationOneYear();
        $emailHitQuoteMetadata->setPath('/');
        $emailHitQuoteMetadata->setHttpOnly(false);

        return $this->cookieManager->setPublicCookie(
            'emailhitquote',
            $currentUrl,
            $emailHitQuoteMetadata
        );
    }
}
