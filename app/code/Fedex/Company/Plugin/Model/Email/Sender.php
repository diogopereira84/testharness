<?php
/**
 * Copyright © FedEx, Inc. All rights reserved.
 */

declare(strict_types=1);

namespace Fedex\Company\Plugin\Model\Email;

use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Company\Model\Email\Sender as CompanyEmailSender;
use Magento\Customer\Api\Data\CustomerInterface;
use Psr\Log\LoggerInterface;

/**
 * Plugin to disable sendAssignSuperUserNotificationEmail method from core Company module
 */
class Sender
{
    /**
     * Constructor
     *
     * @param LoggerInterface $logger
     * @param ToggleConfig $toggleConfig
     */
    public function __construct(
        private LoggerInterface $logger,
        private ToggleConfig $toggleConfig
    ) {}

    /**
     * Conditionally disable sendAssignSuperUserNotificationEmail method based on toggle
     *
     * @param CompanyEmailSender $subject
     * @param callable $proceed
     * @param CustomerInterface $customer
     * @param int $companyId
     * @return CompanyEmailSender
     */
    public function aroundSendAssignSuperUserNotificationEmail(
        CompanyEmailSender $subject,
        callable $proceed,
        CustomerInterface $customer,
        int $companyId
    ): CompanyEmailSender {
        // Check if the toggle is enabled to block emails
        if ($this->toggleConfig->getToggleConfigValue('tech_titans_D_230786')) {
            // Log that the email was blocked
            $this->logger->info(
                'FedEx Company Plugin: sendAssignSuperUserNotificationEmail blocked for customer ID: ' . 
                $customer->getId() . ', Company ID: ' . $companyId . ' due to toggle being enabled'
            );

            // Return subject without calling original method (blocks the email)
            return $subject;
        } else {
            // Log that the email is being sent
            $this->logger->info(
                'FedEx Company Plugin: sendAssignSuperUserNotificationEmail proceeding for customer ID: ' . 
                $customer->getId() . ', Company ID: ' . $companyId . ' as toggle is disabled'
            );

            // Call the original method to send the email
            return $proceed($customer, $companyId);
        }
    }
}