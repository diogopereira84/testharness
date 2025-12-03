<?php
/**
 * @category    Fedex
 * @package     Fedex_Canva
 * @copyright   Copyright (c) 2022 Fedex
 * @author      Jonatan Santos <jsantos@mcfadyen.com>
 */
declare(strict_types=1);

namespace Fedex\SSO\Model;

use Fedex\Canva\Model\CanvaCredentials;
use Fedex\Base\Helper\Auth as AuthHelper;
use Fedex\SSO\Helper\Data;
use Fedex\SSO\ViewModel\SsoConfiguration;
use Magento\Customer\Model\Session;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Psr\Log\LoggerInterface;

class Login
{

    /**
     * Context
     *
     * @var \Magento\Framework\App\Action\Context
     */
    protected $context;

    /**
     * Session
     *
     * @var \Magento\Customer\Model\Session
     */
    protected $session;

    /**
     * @var Fedex\Delivery\Helper\Data
     */
    private $deliveryHelper;


    /**
     * Initialize dependencies.
     *
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param Session $customerSession
     * @param CookieManagerInterface $cookieManagerInterface
     * @param SsoConfiguration $ssoConfiguration
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param JsonFactory $resultJsonFactory
     * @param CanvaCredentials $canvaCredentials
     * @param \Fedex\Delivery\Helper\Data $deliveryHelper
     * @param AuthHelper $authHelper
     */
    public function __construct(
        /**
         * CookieMetadataFactory
         */
        protected \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Customer\Model\Session $customerSession,
        /**
         * cookieManagerInterface
         */
        protected \Magento\Framework\Stdlib\CookieManagerInterface $cookieManagerInterface,
        /**
         * SsoConfiguration
         */
        protected \Fedex\SSO\ViewModel\SsoConfiguration $ssoConfiguration,
        /**
         * Data
         */
        protected \Fedex\SSO\Helper\Data $helper,
        /**
         * LoggerInterface
         */
        protected \Psr\Log\LoggerInterface $logger,
        /**
         * JsonFactory
         */
        protected \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        private CanvaCredentials $canvaCredentials,
        \Fedex\Delivery\Helper\Data $deliveryHelper,
        protected AuthHelper $authHelper
    ) {
        $this->session = $customerSession;
	    $this->deliveryHelper = $deliveryHelper;
    }

    /**
     * Login post action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function isCustomerLoggedIn()
    {
        $resultJson = $this->resultJsonFactory->create();
        try {
            $resultJson = $this->resultJsonFactory->create();
            if (!$this->authHelper->isLoggedIn() && !$this->deliveryHelper->isCommercialCustomer()) {
                $metadata = $this->cookieMetadataFactory
                    ->createPublicCookieMetadata()
                    ->setDomain(".fedex.com")
                    ->setPath("/")
                    ->setHttpOnly(false)
                    ->setSecure(true)
                    ->setSameSite("None");
                $endUrl = $this->ssoConfiguration->getConfigValue("sso/general/profile_api_url");
                if ($this->helper->getFCLCookieNameToggle()) {
                    $cookieName = $this->helper->getFCLCookieConfigValue();
                    $fdxLogin = $this->cookieManagerInterface->getCookie($cookieName);
                } else {
                    $fdxLogin = $this->cookieManagerInterface->getCookie('fdx_login');
                }
                if ($fdxLogin != 'no' && !empty($fdxLogin)) {
                    $this->cookieManagerInterface->setPublicCookie("fcl_customer_login", true, $metadata);

                    $this->logger->info(__METHOD__.':'.__LINE__.':FCL customer login');

                    $getFCLProfile = $this->helper->getCustomerProfile($endUrl, $fdxLogin);
                    if ($getFCLProfile === 401) {
                        return $resultJson->setData([
                            'message' => 'Cookie Expired',
                            'success' => 'expired'
                        ]);
                    }
                    $this->cookieManagerInterface->deleteCookie('mage-cache-sessid', $metadata);

                    if ($getFCLProfile) {
                        $this->cookieManagerInterface->setPublicCookie("fcl_customer_login_success", true, $metadata);
                        $this->canvaCredentials->fetch();
                        return $resultJson->setData([
                            'message' => 'Login Success',
                            'success' => true
                        ]);
                    } else {
                        $this->session->setProfileRetrieveError(true);
                        $this->session->setLoginError(true);
                        return $resultJson->setData([
                            'message' => 'Login Error',
                            'success' => 'error'
                        ]);
                    }
                } else {
                    return $resultJson->setData([
                        'message' => 'Logout Success',
                        'success' => true
                    ]);

                }
            } else {
                return $resultJson->setData([
                    'message' => 'Already Login With Customer Session',
                    'success' => true
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__.':'.__LINE__.':'.$e->getMessage());
            return $resultJson->setData([
                'message' => $e->getMessage(),
                'success' => false
            ]);
        }
    }
}
