<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Fedex\OrderApprovalB2b\Helper;

use Magento\Checkout\Helper\Data as CheckoutHelper;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Fedex\Company\Helper\Data as CompanyHelper;
use Magento\Customer\Model\Session;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http;

/**
 * OrderApprovalB2b AdminConfigHelper class
 */
class AdminConfigHelper extends AbstractHelper
{
    public const XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_request_confirmation_email_template';
    public const XML_PATH_B2B_ORDER_REQUEST_CONFIRMATION_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_request_confirmation_email_enable';
    public const XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_request_decline_email_template';
    public const XML_PATH_B2B_ORDER_REQUEST_DECLINE_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_request_decline_email_enable';
    public const XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_admin_review_email_template';
    public const XML_PATH_B2B_ORDER_ADMIN_REVIEW_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_admin_review_email_enable';
    public const XML_PATH_B2B_ORDER_EXPIRED_EMAIL_TEMPLATE =
        'fedex/transactional_email/b2b_order_expired_email_template';
    public const XML_PATH_B2B_ORDER_EXPIRED_EMAIL_ENABLE =
        'fedex/transactional_email/b2b_order_expired_email_enable';
    public const CONFIRMED = 'confirmed';
    public const DECLINE = 'decline';
    public const REVIEW = 'review';
    public const EXPIRED = 'expired';
    public const CONFIG_BASE_PATH = 'fedex/b2b_order_approval_config/';

    /**
     * OrderApprovalB2b Constructor
     *
     * @param Context $context
     * @param ToggleConfig $toggleConfig
     * @param CompanyHelper $companyHelper
     * @param Session $customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param CheckoutHelper $checkoutHelper
     * @param Http $request
     */
    public function __construct(
        Context $context,
        protected ToggleConfig $toggleConfig,
        protected CompanyHelper $companyHelper,
        private Session      $customerSession,
        private CustomerRepositoryInterface $customerRepository,
        protected CheckoutHelper $checkoutHelper,
        protected Http $request
    ) {
        parent::__construct($context);
    }

    /**
     * Check Order Approval B2B is enabled or not
     *
     * @return boolean
     */
    public function isOrderApprovalB2bEnabled()
    {
        $isOrderApprovalEnabled = false;
        if ($this->isOrderApprovalB2bGloballyEnabled() && $this->isOrderApprovalB2bCompanySettingEnabled()) {
            $isOrderApprovalEnabled = true;
        }

        return $isOrderApprovalEnabled;
    }

    /**
     * To get the B2B Order Approval Config Value
     *
     * @param string $key
     * @param int|null $storeId
     * @return bool|string
     */
    public function getB2bOrderApprovalConfigValue($key, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_BASE_PATH . $key,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check Order Approval B2B is globally enabled or not
     *
     * @return boolean
     */
    public function isOrderApprovalB2bGloballyEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_order_approval_b2b');
    }

    /**
     * Check Order Approval B2B company setting is enabled
     *
     * @return boolean
     */
    public function isOrderApprovalB2bCompanySettingEnabled(): bool
    {
        $customer = $this->getCustomer();
        if (!$customer) {
            return false;
        }
        $companyAttributes = $customer->getExtensionAttributes()->getCompanyAttributes();
        if ($companyAttributes) {
            $companyId = $companyAttributes->getCompanyId();
            if ($companyId) {
                $company = $this->companyHelper->getCustomerCompany($companyId);
                if ($company) {
                    $companyAdditionalData = $company->getExtensionAttributes()->getCompanyAdditionalData();

                    return (bool)$companyAdditionalData->getIsApprovalWorkflowEnabled();
                }
            }
        }

        return false;
    }

    /**
     * Retrieve the current customer
     *
     * @return obj
     */
    private function getCustomer()
    {
        $customerId = $this->customerSession->getCustomerId();
        if ($customerId) {

            return $this->customerRepository->getById($customerId);
        }
    }

    /**
     * Get b2b order email template
     *
     * @param string $configPath
     * @return string
     */
    public function getB2bOrderEmailTemplate($configPath)
    {
        return (string) $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check isB2bOrderEmailEnabled setting is enabled
     *
     * @param string $configPath
     * @return boolean
     */
    public function isB2bOrderEmailEnabled($configPath)
    {
        $value = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
        if ($this->isOrderApprovalB2bEnabled() && $value) {
            return true;
        }

        return false;
    }

    /**
     * Check isB2bOrderEmailEnabledForExireCronEmail setting is enabled
     *
     * @param string $configPath
     * @return boolean
     */
    public function isB2bOrderEmailEnabledForExpireCronEmail($configPath)
    {
        $value = $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
        if ($this->isOrderApprovalB2bGloballyEnabled() && $value) {
            return true;
        }

        return false;
    }

    /**
     * Convert price
     *
     * @param double $price
     * @return string
     */
    public function convertPrice($price)
    {
        return $this->checkoutHelper->convertPrice($price);
    }

    /**
     * Check is review action is set or not
     *
     * @return boolean
     */
    public function checkIsReviewActionSet()
    {
        $isReviewAction = false;
        $action = $this->request->getParam('action') ?? '';
        if ($action == 'review') {
            $isReviewAction = true;
        }

        return $isReviewAction;
    }

    /**
     * Check Decline Order Reorder enabled or not
     *
     * @return boolean
     */
    public function isB2bDeclineReorderEnabled()
    {
        return $this->toggleConfig->getToggleConfigValue('xmen_b2b_order_declined_reorder');
    }

}
