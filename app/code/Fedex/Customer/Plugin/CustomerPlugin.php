<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Fedex\Customer\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\LoginAsCustomerAssistance\Api\SetAssistanceInterface;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Api\CompanyRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin for Customer assistance_allowed extension attribute.
 */
class CustomerPlugin
{
    /**
     * @param SetAssistanceInterface $setAssistance
     * @param ToggleConfig $toggleConfig
     * @param CompanyRepositoryInterface $CompanyRepositoryInterface
     * @param LoggerInterface $loggerInterface
     */
    public function __construct(
        private SetAssistanceInterface $setAssistance,
        protected ToggleConfig $toggleConfig,
        protected CompanyRepositoryInterface $CompanyRepositoryInterface,
        protected LoggerInterface $loggerInterface
    )
    {
    }

    /**
     * Save assistance_allowed extension attribute for Customer instance.
     *
     * @param CustomerRepositoryInterface $subject
     * @param CustomerInterface $result
     * @param CustomerInterface $customer
     * @return CustomerInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSave(
        CustomerRepositoryInterface $subject,
        CustomerInterface $result,
        CustomerInterface $customer
    ): CustomerInterface {
        try {
            if ($this->toggleConfig->getToggleConfigValue('mazegeeks_ctc_admin_impersonator')) {
                $customerExtensionAttributes = $customer->getExtensionAttributes();
                $customer_status_attribute = $customer->getCustomAttribute('customer_status');
                $companyCustomerAttributes = $customerExtensionAttributes->getCompanyAttributes();
                if ($companyCustomerAttributes->getCompanyId()) {
                    $company = $this->CompanyRepositoryInterface->get($companyCustomerAttributes->getCompanyId());
                    $login_method = null;
                    $customer_status = null;
                    $account_status = null;
                    if (!empty($company)) {
                        $login_method = $company->getData('storefront_login_method_option');
                        if (!empty($login_method) && !empty($companyCustomerAttributes) && !empty($customer_status_attribute)) {
                            $account_status = $companyCustomerAttributes->getStatus();
                            $customer_status = $customer_status_attribute->getValue();
                            if ($login_method != "commercial_store_epro" && $account_status == 1 && $customer_status == 1) {
                                $customerExtensionAttributes->setAssistanceAllowed(2);
                            } elseif ($login_method == "commercial_store_epro" && $account_status == 1) {
                                $customerExtensionAttributes->setAssistanceAllowed(assistanceAllowed: 2);
                            } else {
                                $customerExtensionAttributes->setAssistanceAllowed(assistanceAllowed: 1);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->loggerInterface->critical(
                __METHOD__ . ':' . __LINE__
                . ':error: ' . $e->getMessage()
            );
        }
        return $result;
    }
}
