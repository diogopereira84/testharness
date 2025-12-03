<?php

namespace Fedex\Customer\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\App\Helper\Context;
use Fedex\EnvironmentManager\ViewModel\ToggleConfig;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\CustomerFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ResourceConnection;

class Customer extends AbstractHelper
{
    /**
     * @param Context $context
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ToggleConfig $toggleConfig
     * @param CustomerFactory $customerFactory
     * @param StoreManagerInterface $storeManager
     * @param ResourceConnection $resourceConnection
     * @param CustomerRepositoryInterface $customerRepositoryInterface
     */
    public function __construct(
        Context $context,
        protected ModuleDataSetupInterface $moduleDataSetup,
        protected ToggleConfig $toggleConfig,
        protected CustomerFactory $customerFactory,
        protected StoreManagerInterface $storeManager,
        protected ResourceConnection $resourceConnection,
        protected CustomerRepositoryInterface $customerRepositoryInterface
    ) {
        parent::__construct($context);
    }

    /**
     * Update New External Identifer in Customer Entity
     *
     * @param string $externalIdentifer
     * @param int $customerId
     * @return boolean
     */
    public function updateExternalIdentifier($externalIdentifer, $customerId, $secondaryEmail = null, $existingCustomer = null)
    {
        try {
            $customerEntityTable = $this->moduleDataSetup->getTable('customer_entity');
            $this->moduleDataSetup->getConnection()->update(
                $customerEntityTable,
                ['external_id' => $externalIdentifer],
                ['entity_id = ?' => $customerId]
            );
            $this->moduleDataSetup->endSetup();
            //Logic to update email address from external id and secondary email from email
            if ($existingCustomer) {
                if ($externalIdentifer) {
                    $existingCustomer->setEmail($externalIdentifer);
                }
                if ($secondaryEmail) {
                    $existingCustomer->setData("secondary_email", $secondaryEmail);
                }
                $existingCustomer->setIsIdentifierExist(true);
                $existingCustomer->save();
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check Customer by identifier
     *
     * @param string $uuidEmail
     * @return null|string
     */
    public function checkCustomerByIdentifier($uuidEmail)
    {
        $customerEmail = null;
        $connection = $this->resourceConnection->getConnection();
        $customerTable = $connection->getTableName('customer_entity');
        $query = $connection->select()->from($customerTable)->where('external_id = ?', $uuidEmail);
        $result = $connection->fetchAll($query);
        if (!empty($result[0])) {
            $customerEmail = $result[0]['email'];
        }

        return $customerEmail;
    }

    /**
     * Get Customer By Email
     *
     * @param string $uuidEmail
     * @return object|boolean
     */
    public function getCustomerByUuid($uuidEmail)
    {
        $customerModel = $this->customerFactory->create();
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();

        // Check customer if available by External id ($uuidEmail)
        $customerEmail = $this->checkCustomerByIdentifier($uuidEmail);
        if (!empty($customerEmail)) {
            $uuidEmail = $customerEmail;
        }

        $customer = $customerModel->setWebsiteId($websiteId)->loadByEmail($uuidEmail);
        if (is_object($customer)) {
            if (!empty($customerEmail)) {
                $customer->setIsIdentifierExist(true);
            }
            return $customer;
        }

        return false;
    }
    
    /**
     * Get Customer By Email
     *
     * @param string $email
     * @return object|boolean
     */
    public function getCustomerByEmail($email)
    {
        $customerModel = $this->customerFactory->create();
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $customer = $customerModel->setWebsiteId($websiteId)->loadByEmail($email);
        if (is_object($customer)) {
            if (!empty($customerEmail)) {
                $customer->setIsIdentifierExist(true);
            }
            return $customer;
        }
        return false;
    }
    
    /**
     * Get Commercial customer Email from profile data
     *
     * @param array $profileData
     * @return string
     */
    public function getCustomerEmail($profileData)
    {
        $customerId = $profileData['address']['customerId'];
        $customerIdArray = explode("/", $customerId);
        $customerId = end($customerIdArray);
        $customerId = filter_var($customerId, FILTER_SANITIZE_EMAIL);
        if (!strpos($customerId, '@')) {
            $customerId = $customerId . '@fedex.com';
        }

        // if length > 255 then trim remaining from beginning
        $customerId = substr($customerId, -255);

        return $customerId;
    }

    /**
     * Get Customer Status
     *
     * @param string $customerId
     * @return string|null
     */
    public function getCustomerStatus($customerId)
    {
        $customer = $this->customerRepositoryInterface->getById($customerId);
        $customerStatus = null;
        $customerStatusAttribute = $customer->getCustomAttribute('customer_status');
        if ($customerStatusAttribute) {
            $customerStatus = $customerStatusAttribute->getValue();
        }
        $status = null;
        $customerStatusMap = [
            'Inactive',
            'Active',
            'Pending For Approval',
            'Email Verification Pending'
        ];

        if (array_key_exists($customerStatus, $customerStatusMap)) {
            $status =  $customerStatusMap[$customerStatus];
        }

        return $status;
    }
}
