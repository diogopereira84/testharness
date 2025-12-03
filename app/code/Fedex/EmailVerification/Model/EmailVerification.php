<?php
/**
 * @category    Fedex
 * @package     Fedex_EmailVerification
 * @copyright   Copyright (c) 2024 Fedex
 * @author      Austin King <austin.king@fedex.com>
 */
declare(strict_types=1);

namespace Fedex\EmailVerification\Model;

use Fedex\EmailVerification\Model\EmailVerificationCustomerFactory;
use Fedex\Login\Model\Config;
use Fedex\SelfReg\Block\Landing;
use Fedex\SelfReg\Helper\SelfReg;
use Magento\Company\Api\CompanyManagementInterface;
use Magento\Company\Api\Data\CompanyCustomerInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\SessionFactory;
use Magento\Framework\Math\Random;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class EmailVerification
{
    /**
     * Constructor
     *
     * @param EmailVerificationCustomerFactory $emailVerificationCustomerFactory
     * @param Config $moduleConfig
     * @param CompanyCustomerInterfaceFactory $compCustInterface
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     * @param CustomerFactory $customerFactory
     * @param Random $randomDataGenerator
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param SessionFactory $customerSession
     * @param CompanyManagementInterface $companyManagement
     * @param SelfReg $selfRegHelper
     * @param Landing $selfRegLanding
     */
    public function __construct(
        protected EmailVerificationCustomerFactory $emailVerificationCustomerFactory,
        protected Config $moduleConfig,
        protected CompanyCustomerInterfaceFactory $compCustInterface,
        protected CustomerRepositoryInterface $customerRepositoryInterface,
        protected CustomerFactory $customerFactory,
        protected Random $randomDataGenerator,
        protected StoreManagerInterface $storeManager,
        protected LoggerInterface $logger,
        protected SessionFactory $customerSession,
        protected CompanyManagementInterface $companyManagement,
        protected SelfReg $selfRegHelper,
        protected Landing $selfRegLanding
    )
    {
    }

    /**
     * Update or create a new record for the customer in the Customer Email Verification table
     *
     * @param int $customerId
     * @param string $customerEmailUuid
     * @return void
     */
    public function updateEmailVerificationCustomer($customerId, $customerEmailUuid)
    {
        $emailVerificationCustomer = $this->emailVerificationCustomerFactory->create();
        $emailVerificationCustomer->load($customerId, 'customer_entity_id');
        $createdDatetime = strtotime(date("Y-m-d H:i:s"));
        $emailExpirationConfigVal = $this->moduleConfig->getLinkExpirationTime();
        $emailLinkExpirationDatetime = strtotime('+' . $emailExpirationConfigVal, $createdDatetime);

        if ($emailVerificationCustomer->getId()) {
            $emailVerificationData = [
                'key_expiration_datetime' => $emailLinkExpirationDatetime
            ];
        } else {
            $customerUuidBinary = Uuid::fromString($customerEmailUuid)->getBytes();

            $emailVerificationData = [
                'verification_key' => $customerUuidBinary,
                'customer_entity_id' => $customerId,
                'key_created_datetime' => $createdDatetime,
                'key_expiration_datetime' => $emailLinkExpirationDatetime
            ];
        }

        $emailVerificationCustomer->addData($emailVerificationData);
        $emailVerificationCustomer->save();
    }

    /**
     * Get Email Verification Link
     *
     * @param string $customerEmailUuid
     * @return string|null
     */
    public function getEmailVerificationLink($customerEmailUuid)
    {
        $emailLinkUrl = null;
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB);
        $frontName = 'emailverification';

        if ($customerEmailUuid && $customerEmailUuid !== '') {
            $urlParameters = '?key=' . $customerEmailUuid;
            $emailLinkUrl = $baseUrl . $frontName . $urlParameters;
        } else {
            $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                ' Unable to generate email verification link because of invalid customer email uuid.');
        }

        return $emailLinkUrl;
    }

    /**
     * Get customer email uuid
     *
     * @param int $customerId
     * @return string
     */
    public function generateCustomerEmailUuid($customerId)
    {
        $emailVerificationCustomer = $this->emailVerificationCustomerFactory->create();
        $emailVerificationCustomer->load($customerId, 'customer_entity_id');

        if ($emailVerificationCustomer->getId()) {
            $customerEmailUuid = Uuid::fromBytes($emailVerificationCustomer->getVerificationKey())->toString();
        } else {
            $uuid = Uuid::uuid4();
            $customerEmailUuid = $uuid->toString();
        }

        return $customerEmailUuid;
    }

    /**
     * Check if verification link the customer used is expired
     *
     * @param EmailVerificationCustomer $emailVerificationCustomer
     * @return bool
     */
    public function isVerificationLinkActive($emailVerificationCustomer)
    {
        $isLinkActive = false;

        try {
            if ($emailVerificationCustomer->getId()) {
                $expirationDatetime = $emailVerificationCustomer->getKeyExpirationDatetime();
                if ($expirationDatetime) {
                    $currentDatetime = strtotime(date("Y-m-d H:i:s"));
                    $expirationDatetime = strtotime($expirationDatetime);
                    $isLinkActive = $currentDatetime < $expirationDatetime ? true : false;
                    if ($isLinkActive) {
                        $customerSession = $this->customerSession->create();
                        $customerSession->unsEmailVerificationErrorMessage();
                    }
                } else {
                    $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                        ' Unable to get verification link expiration time from Customer Email Verification table.');
                }
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__ .
                    ' Could not find customer for email verification.');
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
            . ' Error while checking if email verification link is active: ' . $e->getMessage());
        }

        return $isLinkActive;
    }

    /**
     * Set Email Verification Expired Link Error Message
     *
     * @param EmailVerificationCustomer $emailVerificationCustomer
     * @return void
     */
    public function setExpiredLinkErrorMessage($emailVerificationCustomer)
    {
        $customerId = $emailVerificationCustomer->getCustomerEntityId();
        $emailVerificationErrorMessage = '';

        if ($customerId) {
            $companyData = $this->companyManagement->getByCustomerId($customerId);
            $companyId = null;

            if ($companyData) {
                $companyId = $companyData->getId();
            }
            $approvalSetting = $this->selfRegHelper->getSettingByCompanyId($companyId);
            $emailVerificationErrorMessage = $approvalSetting['fcl_user_email_verification_error_message'] ?? '';

            if ($emailVerificationErrorMessage != '') {
                $loginLink = '<a href="' . $this->selfRegLanding->getLoginUrl() . '">log in</a>';
                $emailVerificationErrorMessage = str_replace('%login', $loginLink, $emailVerificationErrorMessage);
            }
        }
        $customerSession = $this->customerSession->create();
        $customerSession->setEmailVerificationErrorMessage($emailVerificationErrorMessage);
    }

    /**
     * Get the customer by their email Uuid
     *
     * @param string $customerEmailUuid
     * @return EmailVerificationCustomer|null
     */
    public function getCustomerByEmailUuid($customerEmailUuid)
    {
        $emailVerificationCustomer = null;

        try {
            $emailVerificationCustomer = $this->emailVerificationCustomerFactory->create();
            $customerUuidBinary = Uuid::fromString($customerEmailUuid)->getBytes();
            $emailVerificationCustomer->load($customerUuidBinary, 'verification_key');
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
                . ' Error while getting customer by email uuid: ' . $e->getMessage());
        }

        return $emailVerificationCustomer;
    }

    /**
     * Change Customer Status
     *
     * @param EmailVerificationCustomer $emailVerificationCustomer
     * @return bool
     */
    public function changeCustomerStatus($emailVerificationCustomer)
    {
        $hasStatusChanged = false;

        try {
            $customerId = $emailVerificationCustomer->getCustomerEntityId();
            $customer = $this->customerRepositoryInterface->getById($customerId);

            if ($customer) {
                $customer->setCustomAttribute('customer_status', 1);
                $customerExtensionAttributes = $customer->getExtensionAttributes();
                $companyCustomerAttributes = $customerExtensionAttributes->getCompanyAttributes();
                if (!$companyCustomerAttributes) {
                    $companyCustomerAttributes = $this->compCustInterface->create();
                }

                $companyCustomerAttributes->setStatus(1);
                $customerExtensionAttributes->setCompanyAttributes($companyCustomerAttributes);
                $customer->setExtensionAttributes($customerExtensionAttributes);

                $this->customerRepositoryInterface->save($customer);

                $emailVerificationCustomer->addData([
                    'key_verified_datetime' => strtotime(date("Y-m-d H:i:s"))
                ]);
                $emailVerificationCustomer->save();

                $hasStatusChanged = true;
            } else {
                $this->logger->critical(__METHOD__ . ':' . __LINE__
                    . ' Could not get customer. Unable to change customer status. Customer ID: ' . $customerId);
            }
        } catch (\Exception $e) {
            $this->logger->critical(__METHOD__ . ':' . __LINE__
                . ' Error while setting customer status: ' . $e->getMessage());
        }

        return $hasStatusChanged;
    }
}
