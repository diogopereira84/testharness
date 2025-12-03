<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\SSO\Controller\Customer;

use Exception;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Psr\Log\LoggerInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\LoginAsCustomerApi\Api\DeleteAuthenticationDataForUserInterface;
use Magento\LoginAsCustomerApi\Api\GetLoggedAsCustomerAdminIdInterface;

/**
 * LoginAsCustomerlogout Controller class
 */
class LoginAsCustomerlogout implements ActionInterface
{
    /** @var string */
    private const USER_LOGOUT_SUCCESS = 'user_logout_success';

    /**
     * @var RedirectInterface
     */
    protected $redirect;

    /**
     * Logout constructor
     *
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param EventManager $eventManager
     * @param Session $customerSession
     * @param LoggerInterface $logger
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ToggleConfig $toggleConfig
     * @param DeleteAuthenticationDataForUserInterface $deleteAuthenticationDataForUser
     */
    public function __construct(
        private RequestInterface $request,
        private ResultFactory $resultFactory,
        private EventManager $eventManager,
        protected Session $customerSession,
        protected LoggerInterface $logger,
        private CookieManagerInterface $cookieManager,
        protected CookieMetadataFactory $cookieMetadataFactory,
        protected ToggleConfig $toggleConfig,
        private DeleteAuthenticationDataForUserInterface $deleteAuthenticationDataForUser,
        private GetLoggedAsCustomerAdminIdInterface $getLoggedAsCustomerAdminId
    )
    {
    }

    /**
     * Customer logout from application.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $isLoggedout = false;
        try{
            $isImpoersonatorEnabled = $this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator');
            if($isImpoersonatorEnabled) {
                $customerId = $this->customerSession->getId();
                if ($customerId) {
                    $this->deleteAuthenticationDataForUser->execute($customerId);
                }
                $this->customerSession->unsLoggedAsCustomerAdmindId();
                $isLoggedout = true;
            }
        } catch (Exception $e) {
            $this->customerSession->unsLoggedAsCustomerAdmindId();
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
            ':Unable to do logout for ' . $this->customerSession->getCustomerId() . ' with error: ' . $e->getMessage());
        }
        $response = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $response->setContents($isLoggedout);

        return $response;
    }
}
