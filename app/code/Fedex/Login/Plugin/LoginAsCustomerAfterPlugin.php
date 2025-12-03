<?php
declare(strict_types=1);

namespace Fedex\Login\Plugin;

use Magento\Framework\Controller\ResultInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\LoginAsCustomerFrontendUi\Controller\Login\Index as LoginAsCustomerIndex;
use Fedex\Login\Helper\Login;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyManagementInterface;

class LoginAsCustomerAfterPlugin
{
    /**
     * Constructor.
     *
     * @param CustomerSession $customerSession
     * @param Login $Login
     * @param ToggleConfig $toggleConfig
     * @param CompanyManagementInterface $companyManagementInterface
     */
    public function __construct(
        protected CustomerSession $customerSession,
        protected Login $login,
        protected ToggleConfig $toggleConfig,
        protected CompanyManagementInterface $companyManagementInterface
    )
    {
    }

    /**
     * After plugin for the execute method.
     *
     * @param LoginAsCustomerIndex $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterExecute(LoginAsCustomerIndex $subject, ResultInterface $result): ResultInterface
    {
        if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator') && $this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomer()->getId();
            $companyId = $this->login->getCompanyId($customerId);
            $this->customerSession->setCommunicationUrl("https://www.fedex.com/fxo/punchoutOrderServlet");
            $company = $this->companyManagementInterface->getByCustomerId($customerId);
            $url_extension= $company->getData('company_url_extention');
            $this->login->getRedirectUrl();
            $this->login->setUrlExtensionCookie($url_extension);
            $this->customerSession->setCustomerCompany($companyId);
        }
        return $result;
    }
}